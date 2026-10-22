<?php

namespace App\Domain\Reporting;

use App\Domain\Fuel\FuelConsumptionCalculator;
use App\Domain\Maintenance\MaintenanceDueQueries;
use App\Enums\VehicleStatus;
use App\Models\Fueling;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Monta os dados do dashboard exclusivamente a partir de {@see FinancialQueries}, para que
 * dashboard e relatórios mostrem os mesmos números para o mesmo filtro.
 *
 * FIPE, valor de compra e despesas são indicadores diferentes: nada aqui soma FIPE a gastos
 * e a compra nunca entra nos totais de despesas.
 */
class DashboardOverview
{
    public function __construct(
        private readonly FinancialQueries $queries,
        private readonly MaintenanceDueQueries $maintenanceDue,
        private readonly FuelConsumptionCalculator $consumption,
    ) {}

    /**
     * @param  array<string, mixed>  $input  query string (period, from, to, vehicle_id)
     * @return array<string, mixed>
     */
    public function build(array $input, ?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        [$filters, $echo] = ReportFilters::fromInput(['period' => $input['period'] ?? null, 'from' => $input['from'] ?? null, 'to' => $input['to'] ?? null, 'vehicle_id' => $input['vehicle_id'] ?? null], $today);

        $vehicleScope = new ReportFilters(Period::all(), $filters->vehicleId);
        $month = $vehicleScope->withPeriod(Period::ofMonth($today->year, $today->month));
        $year = $vehicleScope->withPeriod(Period::ofYear($today->year));
        $nextMonths = $vehicleScope->withPeriod(Period::between($today, $today->addMonthsNoOverflow(3)->endOfMonth()));

        $hero = $this->heroVehicle($filters);

        return [
            'filters' => $echo,
            'period' => [
                'from' => $filters->period->unbounded ? null : $filters->period->from->toDateString(),
                'to' => $filters->period->unbounded ? null : $filters->period->to->toDateString(),
            ],
            'vehicle' => $hero,
            'fuelLabel' => $hero?->fuel?->label(),
            'monthLabel' => mb_strtoupper($today->translatedFormat('F \d\e Y')),
            'vehicles' => Vehicle::query()->orderBy('name')->get(['id', 'name', 'brand', 'model', 'version', 'status'])
                ->map(fn (Vehicle $vehicle) => ['value' => $vehicle->id, 'label' => $vehicle->full_name])->values()->all(),
            'kpis' => [
                'active_vehicles' => Vehicle::query()->active()->when($filters->vehicleId, fn ($query, $id) => $query->whereKey($id))->count(),
                'month_cents' => $this->queries->totals($month)['total_cents'],
                'year_cents' => $this->queries->totals($year)['total_cents'],
                'total_cents' => $this->queries->totals($vehicleScope)['total_cents'],
                'forecast_cents' => $this->queries->forecastVsPaid($nextMonths)['forecast_cents'],
                // Preenchido na Fase 5; até lá o indicador aparece como "não consultado".
                'fipe_cents' => null,
            ],
            'indicators' => $this->indicators($hero, $today),
            'charts' => [
                'monthly' => $this->queries->monthlySeries($filters),
                'categories' => $this->queries->byCategory($filters),
                'vehicles' => $this->queries->byVehicle($filters),
            ],
            'upcoming' => $this->upcoming($filters, $today),
            'completeness' => $this->queries->completeness($filters),
        ];
    }

    private function heroVehicle(ReportFilters $filters): ?Vehicle
    {
        if ($filters->vehicleId !== null) {
            return Vehicle::query()->find($filters->vehicleId);
        }

        return Vehicle::query()
            ->where('status', VehicleStatus::Active)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Indicadores do veículo em destaque: gasto do mês, custo por km, consumo e próxima manutenção.
     * Sem dados suficientes devolvem null (a interface mostra "—", nunca zero inventado).
     *
     * @return array<string, mixed>
     */
    private function indicators(?Vehicle $vehicle, CarbonImmutable $today): array
    {
        if ($vehicle === null) {
            return ['month_cents' => null, 'cost_per_km_cents' => null, 'average_consumption' => null, 'next_maintenance' => null];
        }

        $month = new ReportFilters(Period::ofMonth($today->year, $today->month), $vehicle->id);
        $summary = $this->queries->vehicleSummary($vehicle, $today);
        $fuelings = Fueling::query()->where('vehicle_id', $vehicle->id)->get();

        return [
            'month_cents' => $this->queries->totals($month)['total_cents'],
            'cost_per_km_cents' => $summary['cost_per_km_cents'],
            'average_consumption' => $this->consumption->summary($fuelings)['average_km_per_liter'],
            'next_maintenance' => $this->maintenanceDue->attention([$vehicle->id], $today, includeOk: true)[0] ?? null,
        ];
    }

    /**
     * Vencimentos de recorrências e manutenções em uma única lista (vencidos primeiro).
     *
     * @return list<array<string, mixed>>
     */
    private function upcoming(ReportFilters $filters, CarbonImmutable $today): array
    {
        $vehicleScope = new ReportFilters(Period::all(), $filters->vehicleId);
        $recurring = $this->queries->upcomingDue($vehicleScope, withinDays: 90, limit: 20, today: $today);

        $vehicleIds = $this->queries->vehicles($vehicleScope)->pluck('vehicles.id')->all();
        $maintenance = array_map(
            fn (array $item) => [...$item, 'expected_amount_cents' => null],
            $this->maintenanceDue->attention($vehicleIds, $today),
        );

        $items = [...$recurring, ...$maintenance];

        usort($items, fn (array $a, array $b) => [$a['due_date'] ?? '9999-12-31', $a['kind'], $a['id']] <=> [$b['due_date'] ?? '9999-12-31', $b['kind'], $b['id']]);

        return array_slice($items, 0, 10);
    }
}
