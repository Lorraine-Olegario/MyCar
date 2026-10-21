<?php

namespace App\Domain\Fipe;

use App\Models\Vehicle;
use Illuminate\Support\Str;

/**
 * Confere se a cotação devolvida é do veículo certo: marca, modelo/versão e ano do modelo.
 * Compara por palavras, sem acentos e sem diferenciar maiúsculas, porque a FIPE escreve
 * o modelo de forma longa (ex.: "Onix 1.0 LT 12V Flex 5p Mec.").
 */
class FipeVehicleMatcher
{
    /**
     * @throws FipeException quando algo diverge
     */
    public function assertMatches(Vehicle $vehicle, FipeQuoteData $quote): void
    {
        if ($quote->modelYear !== (int) $vehicle->model_year) {
            throw FipeException::mismatch("o ano do modelo é {$quote->modelYear}, mas o veículo é {$vehicle->model_year}.");
        }

        if (! $this->brandMatches($vehicle->brand, $quote->brand)) {
            throw FipeException::mismatch("a marca é {$quote->brand}, mas o veículo é {$vehicle->brand}.");
        }

        $fipeTokens = $this->tokens($quote->model);

        if (! $this->containsAll($fipeTokens, $this->tokens((string) $vehicle->model))) {
            throw FipeException::mismatch("o modelo é {$quote->model}, mas o veículo é {$vehicle->model}.");
        }

        if (filled($vehicle->version) && ! $this->containsAll($fipeTokens, $this->tokens((string) $vehicle->version))) {
            throw FipeException::mismatch("a versão é {$quote->model}, mas o veículo é {$vehicle->model} {$vehicle->version}.");
        }
    }

    private function brandMatches(string $vehicleBrand, string $fipeBrand): bool
    {
        $a = $this->normalize($vehicleBrand);
        $b = $this->normalize($fipeBrand);

        return $a !== '' && $b !== '' && (str_contains($b, $a) || str_contains($a, $b));
    }

    /**
     * @param  list<string>  $haystack
     * @param  list<string>  $needles
     */
    private function containsAll(array $haystack, array $needles): bool
    {
        return $needles !== [] && array_diff($needles, $haystack) === [];
    }

    /**
     * @return list<string>
     */
    private function tokens(string $value): array
    {
        return array_values(array_filter(explode(' ', $this->normalize($value))));
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9.]+/', ' ', Str::lower(Str::ascii($value))) ?? '');
    }
}
