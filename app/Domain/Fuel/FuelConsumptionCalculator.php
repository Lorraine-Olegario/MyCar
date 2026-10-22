<?php

namespace App\Domain\Fuel;

use App\Models\Fueling;
use Illuminate\Support\Collection;

/**
 * Consumo pelo método do tanque cheio.
 *
 * Só existe km/l entre dois abastecimentos de tanque cheio consecutivos: a distância
 * vai do odômetro do cheio anterior ao do cheio atual, e os litros somam os
 * abastecimentos parciais intermediários mais o cheio atual. Sem esses dois pontos
 * o resultado é `null` (nunca zero ou valor estimado).
 */
class FuelConsumptionCalculator
{
    /**
     * Ordena cronologicamente (data, odômetro, id) para que o cálculo não dependa da ordem de entrada.
     *
     * @param  Collection<int, Fueling>  $fuelings
     * @return Collection<int, Fueling>
     */
    public function chronological(Collection $fuelings): Collection
    {
        return $fuelings
            ->sort(fn (Fueling $a, Fueling $b) => [$a->fueled_on->timestamp, $a->odometer, $a->id]
                <=> [$b->fueled_on->timestamp, $b->odometer, $b->id])
            ->values();
    }

    /**
     * Segmentos de consumo, indexados pelo id do abastecimento que fecha o tanque.
     * Abastecimentos sem segmento calculável aparecem com todos os campos nulos e o motivo.
     *
     * @param  Collection<int, Fueling>  $fuelings  abastecimentos de um único veículo
     * @return array<int, array{distance_km: ?int, liters: ?float, km_per_liter: ?float, cost_per_km_cents: ?float, cost_cents: ?int, reason: ?string}>
     */
    public function segments(Collection $fuelings): array
    {
        $segments = [];
        $previousFull = null;
        $pending = [];

        foreach ($this->chronological($fuelings) as $fueling) {
            $pending[] = $fueling;

            if (! $fueling->full_tank) {
                $segments[$fueling->id] = $this->empty('Tanque parcial: o consumo é calculado no próximo tanque cheio.');

                continue;
            }

            if ($previousFull === null) {
                $segments[$fueling->id] = $this->empty('Primeiro tanque cheio: falta um cheio anterior como referência.');
            } else {
                $segments[$fueling->id] = $this->segment($previousFull, $fueling, $pending);
            }

            $previousFull = $fueling;
            $pending = [];
        }

        return $segments;
    }

    /**
     * Resumo agregado: litros, valor pago, preço médio ponderado por litro e, quando houver
     * ao menos um segmento válido, consumo e custo por km do conjunto (distância total ÷ litros totais).
     *
     * @param  Collection<int, Fueling>  $fuelings
     * @return array{count: int, total_liters: float, total_cents: int, average_price_cents_per_liter: ?int, average_km_per_liter: ?float, cost_per_km_cents: ?float, distance_km: int}
     */
    public function summary(Collection $fuelings): array
    {
        $totalLiters = (float) $fuelings->sum(fn (Fueling $fueling) => (float) $fueling->liters);
        $totalCents = (int) $fuelings->sum('total_cents');

        $distance = 0;
        $segmentLiters = 0.0;
        $segmentCost = 0;

        foreach ($this->segments($fuelings) as $segment) {
            if ($segment['km_per_liter'] === null) {
                continue;
            }

            $distance += $segment['distance_km'];
            $segmentLiters += $segment['liters'];
            $segmentCost += $segment['cost_cents'];
        }

        return [
            'count' => $fuelings->count(),
            'total_liters' => round($totalLiters, 3),
            'total_cents' => $totalCents,
            'average_price_cents_per_liter' => $totalLiters > 0 ? (int) round($totalCents / $totalLiters) : null,
            'average_km_per_liter' => $distance > 0 && $segmentLiters > 0 ? round($distance / $segmentLiters, 2) : null,
            'cost_per_km_cents' => $distance > 0 ? round($segmentCost / $distance, 2) : null,
            'distance_km' => $distance,
        ];
    }

    /**
     * @param  list<Fueling>  $interval  abastecimentos depois do cheio anterior, até o cheio atual (inclusive)
     * @return array{distance_km: ?int, liters: ?float, km_per_liter: ?float, cost_per_km_cents: ?float, cost_cents: ?int, reason: ?string}
     */
    private function segment(Fueling $previousFull, Fueling $current, array $interval): array
    {
        $distance = $current->odometer - $previousFull->odometer;
        $liters = array_sum(array_map(fn (Fueling $fueling) => (float) $fueling->liters, $interval));
        $cost = array_sum(array_map(fn (Fueling $fueling) => $fueling->total_cents, $interval));

        if ($distance <= 0) {
            return $this->empty('Odômetro sem avanço entre os dois tanques cheios.');
        }

        if ($liters <= 0) {
            return $this->empty('Litros não informados no intervalo.');
        }

        return [
            'distance_km' => $distance,
            'liters' => round($liters, 3),
            'km_per_liter' => round($distance / $liters, 2),
            'cost_per_km_cents' => round($cost / $distance, 2),
            'cost_cents' => $cost,
            'reason' => null,
        ];
    }

    /**
     * @return array{distance_km: null, liters: null, km_per_liter: null, cost_per_km_cents: null, cost_cents: null, reason: string}
     */
    private function empty(string $reason): array
    {
        return [
            'distance_km' => null,
            'liters' => null,
            'km_per_liter' => null,
            'cost_per_km_cents' => null,
            'cost_cents' => null,
            'reason' => $reason,
        ];
    }
}
