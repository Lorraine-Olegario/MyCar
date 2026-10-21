<?php

namespace App\Domain\Reporting\Reports;

use App\Domain\Fuel\FuelingOverview;
use App\Domain\Reporting\FinancialQueries;
use App\Domain\Reporting\ReportFilters;
use App\Models\Fueling;
use Illuminate\Support\Collection;

/**
 * Relatório 6 — histórico de abastecimentos e consumo (método do tanque cheio).
 */
class FuelHistoryReport extends Report
{
    public function __construct(FinancialQueries $queries, private readonly FuelingOverview $overview)
    {
        parent::__construct($queries);
    }

    public function key(): string
    {
        return 'fuel-history';
    }

    public function title(): string
    {
        return 'Abastecimentos e consumo';
    }

    public function description(): string
    {
        return 'Cada abastecimento com km percorridos, km/l e custo por km entre tanques cheios; sem dados suficientes, o campo fica em branco.';
    }

    public function columns(ReportFilters $filters, array $rows): array
    {
        return [
            ['key' => 'fueled_on', 'label' => 'Data', 'type' => 'date'],
            ['key' => 'vehicle', 'label' => 'Veículo', 'type' => 'text'],
            ['key' => 'odometer', 'label' => 'Odômetro', 'type' => 'km'],
            ['key' => 'liters', 'label' => 'Litros', 'type' => 'decimal3'],
            ['key' => 'price_per_liter', 'label' => 'Preço por litro', 'type' => 'price'],
            ['key' => 'total_cents', 'label' => 'Valor', 'type' => 'currency'],
            ['key' => 'tank', 'label' => 'Tanque', 'type' => 'text'],
            ['key' => 'distance_km', 'label' => 'Km percorridos', 'type' => 'km'],
            ['key' => 'km_per_liter', 'label' => 'Consumo (km/l)', 'type' => 'decimal2'],
            ['key' => 'cost_per_km_cents', 'label' => 'Custo por km', 'type' => 'currency_decimal'],
        ];
    }

    /**
     * @return Collection<int, Fueling>
     */
    private function fuelings(ReportFilters $filters)
    {
        return Fueling::query()
            ->with('vehicle:id,name')
            ->whereIn('vehicle_id', $this->queries->vehicles($filters)->select('vehicles.id'))
            ->whereBetween('fueled_on', [$filters->period->from->toDateString(), $filters->period->to->toDateString()])
            ->orderByDesc('fueled_on')
            ->orderByDesc('odometer')
            ->orderByDesc('id')
            ->get();
    }

    public function rows(ReportFilters $filters): array
    {
        $fuelings = $this->fuelings($filters);
        $segments = $this->overview->segmentsFor($fuelings);

        return $fuelings->map(fn (Fueling $fueling) => [
            'fueled_on' => $fueling->fueled_on->toDateString(),
            'vehicle' => $fueling->vehicle?->name,
            'odometer' => $fueling->odometer,
            'liters' => (float) $fueling->liters,
            'price_per_liter' => (float) $fueling->price_per_liter,
            'total_cents' => $fueling->total_cents,
            'tank' => $fueling->full_tank ? 'Cheio' : 'Parcial',
            'distance_km' => $segments[$fueling->id]['distance_km'] ?? null,
            'km_per_liter' => $segments[$fueling->id]['km_per_liter'] ?? null,
            'cost_per_km_cents' => $segments[$fueling->id]['cost_per_km_cents'] ?? null,
        ])->all();
    }

    public function totals(ReportFilters $filters, array $rows): ?array
    {
        $fuelings = $this->fuelings($filters);
        $summary = $this->overview->summary($fuelings, $this->overview->segmentsFor($fuelings));

        return [
            'fueled_on' => 'Total',
            'liters' => $summary['total_liters'],
            'price_per_liter' => $summary['average_price_cents_per_liter'] !== null ? round($summary['average_price_cents_per_liter'] / 100, 3) : null,
            'total_cents' => $summary['total_cents'],
            'distance_km' => $summary['distance_km'] > 0 ? $summary['distance_km'] : null,
            'km_per_liter' => $summary['average_km_per_liter'],
            'cost_per_km_cents' => $summary['cost_per_km_cents'],
        ];
    }

    public function paginated(): bool
    {
        return true;
    }

    public function defaultPeriod(): string
    {
        return 'all';
    }

    public function supports(): array
    {
        return ['category' => false, 'period' => true, 'scope' => true];
    }
}
