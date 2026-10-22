<?php

namespace App\Domain\Fipe;

use App\Models\FipeQuote;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Consulta a FIPE e persiste o resultado.
 *
 * - O mês de referência é o devolvido pela própria fonte.
 * - A combinação precisa corresponder ao veículo; divergências são rejeitadas.
 * - Em sucesso, em uma transação: grava o histórico e atualiza os campos atuais do veículo.
 * - Em falha, nada muda (nem campo atual, nem histórico) e a exceção é tipada. O valor
 *   anterior continua disponível por {@see snapshot()} com `is_stale`. Nenhum valor é inventado.
 */
class FipeQuoteService
{
    public function __construct(
        private readonly FipeClient $client,
        private readonly FipeVehicleMatcher $matcher,
    ) {}

    /**
     * @throws FipeException
     */
    public function consult(Vehicle $vehicle, FipeSelection $selection): FipeQuote
    {
        // Falhas de rede/contrato e divergências acontecem antes de qualquer escrita.
        $data = $this->client->quote($selection->type, $selection->brandId, $selection->modelId, $selection->yearId);
        $this->matcher->assertMatches($vehicle, $data);

        return DB::transaction(function () use ($vehicle, $selection, $data): FipeQuote {
            $consultedAt = now();

            $quote = $vehicle->fipeQuotes()->create([
                'fipe_code' => $data->codeFipe,
                'value_cents' => $data->valueCents,
                'reference_month' => $data->referenceMonth,
                'brand' => $data->brand,
                'model' => $data->model,
                'model_year' => $data->modelYear,
                'fuel' => $data->fuel,
                'consulted_at' => $consultedAt,
                'payload' => $data->payload,
            ]);

            $vehicle->forceFill([
                'fipe_vehicle_type' => $selection->type->value,
                'fipe_brand_id' => $selection->brandId,
                'fipe_model_id' => $selection->modelId,
                'fipe_year_id' => $selection->yearId,
                'fipe_code' => $data->codeFipe,
                'fipe_value_cents' => $data->valueCents,
                'fipe_reference_month' => $data->referenceMonth,
                'fipe_consulted_at' => $consultedAt,
            ])->save();

            return $quote;
        });
    }

    /**
     * Atualiza usando a seleção já guardada no veículo.
     *
     * @throws FipeException
     */
    public function refresh(Vehicle $vehicle): FipeQuote
    {
        $selection = $this->storedSelection($vehicle);

        if ($selection === null) {
            throw FipeException::notFound();
        }

        return $this->consult($vehicle, $selection);
    }

    public function storedSelection(Vehicle $vehicle): ?FipeSelection
    {
        $type = FipeVehicleType::tryFrom((string) $vehicle->fipe_vehicle_type);

        if ($type === null || blank($vehicle->fipe_brand_id) || blank($vehicle->fipe_model_id) || blank($vehicle->fipe_year_id)) {
            return null;
        }

        return new FipeSelection($type, $vehicle->fipe_brand_id, $vehicle->fipe_model_id, $vehicle->fipe_year_id);
    }

    /**
     * Última cotação bem-sucedida do veículo, ou null se nunca houve. `is_stale` indica que
     * ela é antiga demais para ser tratada como atual (padrão: mais de 35 dias).
     *
     * @return array{value_cents: int, reference_month: string, consulted_at: string, brand: ?string, model: ?string, model_year: ?int, fuel: ?string, fipe_code: string, is_stale: bool, stale_after_days: int}|null
     */
    public function snapshot(Vehicle $vehicle, ?Carbon $now = null): ?array
    {
        if ($vehicle->fipe_value_cents === null || $vehicle->fipe_consulted_at === null) {
            return null;
        }

        $now ??= now();
        $days = (int) config('services.fipe.stale_days');
        $quote = $vehicle->latestFipeQuote;

        return [
            'value_cents' => $vehicle->fipe_value_cents,
            'reference_month' => (string) $vehicle->fipe_reference_month,
            'consulted_at' => $vehicle->fipe_consulted_at->toIso8601String(),
            'brand' => $quote?->brand,
            'model' => $quote?->model,
            'model_year' => $quote?->model_year,
            'fuel' => $quote?->fuel,
            'fipe_code' => (string) $vehicle->fipe_code,
            'is_stale' => $vehicle->fipe_consulted_at->lt($now->copy()->subDays($days)),
            'stale_after_days' => $days,
        ];
    }
}
