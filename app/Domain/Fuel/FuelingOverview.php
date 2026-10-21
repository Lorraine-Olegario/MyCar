<?php

namespace App\Domain\Fuel;

use App\Models\Fueling;
use Illuminate\Support\Collection;

/**
 * Monta os números exibidos na página de abastecimentos a partir de um filtro.
 *
 * O consumo de cada abastecimento é sempre calculado sobre o histórico completo do
 * veículo (não só sobre a página ou o filtro), para que o km/l não mude conforme o recorte.
 */
class FuelingOverview
{
    public function __construct(private readonly FuelConsumptionCalculator $calculator) {}

    /**
     * Segmentos de consumo indexados pelo id do abastecimento, para os veículos do recorte.
     *
     * @param  Collection<int, Fueling>  $filtered
     * @return array<int, array<string, mixed>>
     */
    public function segmentsFor(Collection $filtered): array
    {
        $segments = [];

        $vehicleIds = $filtered->pluck('vehicle_id')->unique()->all();

        Fueling::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->get()
            ->groupBy('vehicle_id')
            ->each(function (Collection $fuelings) use (&$segments): void {
                $segments += $this->calculator->segments($fuelings);
            });

        return $segments;
    }

    /**
     * Totais do recorte: litros, valor, preço médio por litro e, quando houver segmentos
     * fechados dentro do recorte, consumo e custo por km agregados.
     *
     * @param  Collection<int, Fueling>  $filtered
     * @param  array<int, array<string, mixed>>  $segments
     * @return array{count: int, total_liters: float, total_cents: int, average_price_cents_per_liter: ?int, average_km_per_liter: ?float, cost_per_km_cents: ?float, distance_km: int}
     */
    public function summary(Collection $filtered, array $segments): array
    {
        $liters = (float) $filtered->sum(fn (Fueling $fueling) => (float) $fueling->liters);
        $cents = (int) $filtered->sum('total_cents');

        $distance = 0;
        $segmentLiters = 0.0;
        $segmentCost = 0;

        foreach ($filtered as $fueling) {
            $segment = $segments[$fueling->id] ?? null;

            if ($segment === null || $segment['km_per_liter'] === null) {
                continue;
            }

            $distance += $segment['distance_km'];
            $segmentLiters += $segment['liters'];
            $segmentCost += $segment['cost_cents'];
        }

        return [
            'count' => $filtered->count(),
            'total_liters' => round($liters, 3),
            'total_cents' => $cents,
            'average_price_cents_per_liter' => $liters > 0 ? (int) round($cents / $liters) : null,
            'average_km_per_liter' => $distance > 0 && $segmentLiters > 0 ? round($distance / $segmentLiters, 2) : null,
            'cost_per_km_cents' => $distance > 0 ? round($segmentCost / $distance, 2) : null,
            'distance_km' => $distance,
        ];
    }

    /**
     * Totais por mês ('Y-m') ou por ano ('Y'), do mais recente ao mais antigo, pela data do abastecimento.
     *
     * @param  Collection<int, Fueling>  $filtered
     * @return list<array{period: string, count: int, liters: float, total_cents: int}>
     */
    public function totalsBy(Collection $filtered, string $format): array
    {
        return $filtered
            ->groupBy(fn (Fueling $fueling) => $fueling->fueled_on->format($format))
            ->map(fn (Collection $group, string $period) => [
                'period' => $period,
                'count' => $group->count(),
                'liters' => round((float) $group->sum(fn (Fueling $fueling) => (float) $fueling->liters), 3),
                'total_cents' => (int) $group->sum('total_cents'),
            ])
            ->sortKeysDesc()
            ->values()
            ->all();
    }
}
