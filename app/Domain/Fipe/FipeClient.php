<?php

namespace App\Domain\Fipe;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cliente da API pública Parallelum FIPE v2.
 *
 * Toda chamada passa por aqui, com timeout curto, retry limitado (só para falhas de
 * conexão e 5xx) e erros tipados em português. O catálogo (marcas, modelos e anos)
 * fica em cache; o valor de um veículo nunca é cacheado — cada consulta é persistida
 * pelo serviço, com o mês de referência devolvido pela fonte.
 */
class FipeClient
{
    private const MONTHS = [
        'janeiro' => 1, 'fevereiro' => 2, 'março' => 3, 'abril' => 4, 'maio' => 5, 'junho' => 6,
        'julho' => 7, 'agosto' => 8, 'setembro' => 9, 'outubro' => 10, 'novembro' => 11, 'dezembro' => 12,
    ];

    /**
     * @return list<FipeOption>
     */
    public function brands(FipeVehicleType $type): array
    {
        return $this->catalog("{$type->value}/brands", "brands:{$type->value}");
    }

    /**
     * @return list<FipeOption>
     */
    public function models(FipeVehicleType $type, string $brandId): array
    {
        return $this->catalog("{$type->value}/brands/{$brandId}/models", "models:{$type->value}:{$brandId}");
    }

    /**
     * @return list<FipeOption>
     */
    public function years(FipeVehicleType $type, string $brandId, string $modelId): array
    {
        return $this->catalog("{$type->value}/brands/{$brandId}/models/{$modelId}/years", "years:{$type->value}:{$brandId}:{$modelId}");
    }

    /**
     * Valor do veículo no ano/versão informados. Nunca usa cache.
     */
    public function quote(FipeVehicleType $type, string $brandId, string $modelId, string $yearId): FipeQuoteData
    {
        $data = $this->get("{$type->value}/brands/{$brandId}/models/{$modelId}/years/{$yearId}");

        return $this->parseQuote($data, $type);
    }

    /**
     * @return list<FipeOption>
     */
    private function catalog(string $path, string $key): array
    {
        $ttl = (int) config('services.fipe.cache_ttl');
        $cacheKey = config('services.fipe.cache_prefix').':'.$key;

        // Só respostas válidas entram no cache: uma falha lança exceção e não é guardada.
        $options = Cache::remember($cacheKey, $ttl, function () use ($path): array {
            return array_map(fn (FipeOption $option) => $option->toArray(), $this->parseOptions($this->get($path)));
        });

        return array_map(fn (array $option) => new FipeOption($option['code'], $option['name']), $options);
    }

    /**
     * @return array<mixed>
     */
    private function get(string $path): array
    {
        try {
            $response = $this->request()->get($path);
        } catch (ConnectionException $exception) {
            throw $this->isTimeout($exception) ? FipeException::timeout($exception) : FipeException::unavailable($exception);
        } catch (RequestException $exception) {
            throw $this->fromStatus($exception->response, $exception);
        }

        if ($response->failed()) {
            throw $this->fromStatus($response);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw FipeException::malformed();
        }

        return $data;
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl(rtrim((string) config('services.fipe.base_url'), '/'))
            ->acceptJson()
            ->timeout((int) config('services.fipe.timeout'))
            ->retry(
                max(1, (int) config('services.fipe.retries')),
                (int) config('services.fipe.retry_sleep_ms'),
                fn (Throwable $exception) => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()),
                throw: false,
            );

        if (filled(config('services.fipe.token'))) {
            $request->withHeaders(['X-Subscription-Token' => (string) config('services.fipe.token')]);
        }

        return $request;
    }

    private function fromStatus(Response $response, ?Throwable $previous = null): FipeException
    {
        return match (true) {
            $response->status() === 404 => FipeException::notFound(),
            $response->status() === 408 || $response->status() === 504 => FipeException::timeout($previous),
            default => FipeException::unavailable($previous),
        };
    }

    private function isTimeout(ConnectionException $exception): bool
    {
        return str_contains(strtolower($exception->getMessage()), 'timed out') || str_contains($exception->getMessage(), 'cURL error 28');
    }

    /**
     * @param  array<mixed>  $data
     * @return list<FipeOption>
     */
    private function parseOptions(array $data): array
    {
        $options = [];

        foreach ($data as $item) {
            if (! is_array($item) || ! isset($item['code'], $item['name']) || ! is_scalar($item['code']) || ! is_string($item['name'])) {
                throw FipeException::malformed();
            }

            $options[] = new FipeOption((string) $item['code'], $item['name']);
        }

        return $options;
    }

    /**
     * @param  array<mixed>  $data
     */
    private function parseQuote(array $data, FipeVehicleType $type): FipeQuoteData
    {
        foreach (['brand', 'model', 'fuel', 'codeFipe', 'price', 'referenceMonth', 'modelYear'] as $field) {
            if (! isset($data[$field]) || (! is_string($data[$field]) && ! is_int($data[$field]))) {
                throw FipeException::malformed();
            }
        }

        $cents = self::parsePrice((string) $data['price']);
        $reference = self::parseReferenceMonth((string) $data['referenceMonth']);

        if ($cents === null || $reference === null || ! is_numeric($data['modelYear'])) {
            throw FipeException::malformed();
        }

        return new FipeQuoteData(
            brand: (string) $data['brand'],
            model: (string) $data['model'],
            modelYear: (int) $data['modelYear'],
            fuel: (string) $data['fuel'],
            codeFipe: (string) $data['codeFipe'],
            valueCents: $cents,
            referenceMonth: $reference,
            vehicleType: $type->value,
            payload: $data,
        );
    }

    /**
     * "R$ 45.678,90" → 4567890 (centavos). Valores inválidos ou zerados retornam null.
     */
    public static function parsePrice(string $price): ?int
    {
        if (preg_match('/^\s*R\$\s*(\d{1,3}(?:\.\d{3})*|\d+),(\d{2})\s*$/u', $price, $matches) !== 1) {
            return null;
        }

        $cents = (int) str_replace('.', '', $matches[1]) * 100 + (int) $matches[2];

        return $cents > 0 ? $cents : null;
    }

    /**
     * "janeiro de 2026" → "2026-01".
     */
    public static function parseReferenceMonth(string $reference): ?string
    {
        if (preg_match('/^\s*([\p{L}ç]+)\s+de\s+(\d{4})\s*$/u', mb_strtolower($reference), $matches) !== 1) {
            return null;
        }

        $month = self::MONTHS[$matches[1]] ?? null;

        return $month === null ? null : sprintf('%04d-%02d', (int) $matches[2], $month);
    }
}
