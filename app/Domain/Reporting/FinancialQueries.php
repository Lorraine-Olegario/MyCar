<?php

namespace App\Domain\Reporting;

use App\Enums\OccurrenceStatus;
use App\Enums\VehicleStatus;
use App\Models\RecurringExpenseOccurrence;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Única fonte dos indicadores financeiros. Dashboard, página do veículo, relatórios e
 * exportações consomem estas consultas, para os números nunca divergirem.
 *
 * Regras: totais usam `occurred_on` (nunca `created_at`); compra não é despesa
 * operacional; previsões de recorrência ficam em consultas próprias e nunca entram
 * nos totais realizados; valores em centavos; agregações feitas no banco.
 */
class FinancialQueries
{
    // ---------------------------------------------------------------- escopo

    /**
     * Veículos considerados pelo filtro.
     *
     * @return Builder<Vehicle>
     */
    public function vehicles(ReportFilters $filters): Builder
    {
        $query = Vehicle::query();

        if ($filters->vehicleId !== null) {
            return $query->withTrashed()->whereKey($filters->vehicleId);
        }

        if ($filters->includeArchived) {
            $query->withTrashed();
        }

        if (! $filters->includeSold) {
            $query->where('status', VehicleStatus::Active);
        }

        return $query;
    }

    /**
     * Despesas realizadas dentro do filtro (já com período, veículo, categoria e escopo).
     * Veículos vendidos só contam entre a aquisição e a venda.
     */
    private function expenses(ReportFilters $filters): QueryBuilder
    {
        $query = DB::table('expenses')
            ->join('vehicles', 'vehicles.id', '=', 'expenses.vehicle_id')
            ->whereBetween('expenses.occurred_on', [$filters->period->from->toDateString(), $filters->period->to->toDateString()])
            ->when($filters->categoryId, fn ($query, $id) => $query->where('expenses.expense_category_id', $id))
            ->whereIn('expenses.vehicle_id', $this->vehicles($filters)->select('vehicles.id'))
            ->where(function ($query): void {
                $query->where('vehicles.status', '!=', VehicleStatus::Sold->value)
                    ->orWhereNull('vehicles.sale_date')
                    // Os campos de data do veículo podem estar gravados com hora; date() normaliza.
                    ->orWhere(function ($query): void {
                        $query->whereRaw('expenses.occurred_on >= date(vehicles.acquisition_date)')
                            ->whereRaw('expenses.occurred_on <= date(vehicles.sale_date)');
                    });
            });

        return $query;
    }

    // ---------------------------------------------------------------- totais

    /**
     * Total realizado no período. `has_data` distingue "sem lançamentos" de um total real.
     *
     * @return array{total_cents: int, count: int, average_cents: ?int, has_data: bool}
     */
    public function totals(ReportFilters $filters): array
    {
        $row = $this->expenses($filters)
            ->selectRaw('coalesce(sum(expenses.amount_cents), 0) as total, count(*) as aggregate_count')
            ->first();

        $total = (int) $row->total;
        $count = (int) $row->aggregate_count;

        return [
            'total_cents' => $total,
            'count' => $count,
            'average_cents' => $count > 0 ? (int) round($total / $count) : null,
            'has_data' => $count > 0,
        ];
    }

    /**
     * Série mensal (um item por mês do período, inclusive meses sem gastos).
     *
     * @return list<array{month: string, total_cents: int, count: int}>
     */
    public function monthlySeries(ReportFilters $filters): array
    {
        return $this->series($filters, 'Y-m');
    }

    /**
     * Série anual (um item por ano do período).
     *
     * @return list<array{month: string, total_cents: int, count: int}>
     */
    public function yearlySeries(ReportFilters $filters): array
    {
        return $this->series($filters, 'Y');
    }

    /**
     * @return list<array{month: string, total_cents: int, count: int}>
     */
    private function series(ReportFilters $filters, string $format): array
    {
        $buckets = [];

        $daily = $this->expenses($filters)
            ->selectRaw('expenses.occurred_on as day, sum(expenses.amount_cents) as total, count(*) as aggregate_count')
            ->groupBy('expenses.occurred_on')
            ->orderBy('expenses.occurred_on')
            ->get();

        foreach ($daily as $row) {
            $key = CarbonImmutable::parse($row->day)->format($format);
            $buckets[$key]['total_cents'] = ($buckets[$key]['total_cents'] ?? 0) + (int) $row->total;
            $buckets[$key]['count'] = ($buckets[$key]['count'] ?? 0) + (int) $row->aggregate_count;
        }

        $keys = $this->bucketKeys($filters->period, $format, array_keys($buckets));

        return array_map(fn (string $key) => [
            'month' => $key,
            'total_cents' => $buckets[$key]['total_cents'] ?? 0,
            'count' => $buckets[$key]['count'] ?? 0,
        ], $keys);
    }

    /**
     * @param  list<string>  $dataKeys
     * @return list<string>
     */
    private function bucketKeys(Period $period, string $format, array $dataKeys): array
    {
        if ($period->unbounded) {
            sort($dataKeys);

            if ($dataKeys === []) {
                return [];
            }

            $period = Period::between($dataKeys[0].($format === 'Y' ? '-01-01' : '-01'), end($dataKeys).($format === 'Y' ? '-12-31' : '-01'));
        }

        return $format === 'Y'
            ? array_map('strval', $period->years())
            : array_map(fn (CarbonImmutable $month) => $month->format('Y-m'), $period->months());
    }

    /**
     * Gastos por categoria, do maior para o menor, com participação percentual.
     *
     * @return list<array{category_id: int, name: string, color: ?string, total_cents: int, count: int, share: float}>
     */
    public function byCategory(ReportFilters $filters): array
    {
        $rows = $this->expenses($filters)
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->selectRaw('expense_categories.id as category_id, expense_categories.name, expense_categories.color, sum(expenses.amount_cents) as total, count(*) as aggregate_count')
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.color')
            ->orderByDesc('total')
            ->orderBy('expense_categories.name')
            ->get();

        $grand = (int) $rows->sum('total');

        return $rows->map(fn ($row) => [
            'category_id' => (int) $row->category_id,
            'name' => $row->name,
            'color' => $row->color,
            'total_cents' => (int) $row->total,
            'count' => (int) $row->aggregate_count,
            'share' => $grand > 0 ? round($row->total / $grand * 100, 1) : 0.0,
        ])->all();
    }

    /**
     * Gastos por veículo, do maior para o menor.
     *
     * @return list<array{vehicle_id: int, name: string, total_cents: int, count: int, share: float}>
     */
    public function byVehicle(ReportFilters $filters): array
    {
        $rows = $this->expenses($filters)
            ->selectRaw('vehicles.id as vehicle_id, vehicles.name, sum(expenses.amount_cents) as total, count(*) as aggregate_count')
            ->groupBy('vehicles.id', 'vehicles.name')
            ->orderByDesc('total')
            ->orderBy('vehicles.name')
            ->get();

        $grand = (int) $rows->sum('total');

        return $rows->map(fn ($row) => [
            'vehicle_id' => (int) $row->vehicle_id,
            'name' => $row->name,
            'total_cents' => (int) $row->total,
            'count' => (int) $row->aggregate_count,
            'share' => $grand > 0 ? round($row->total / $grand * 100, 1) : 0.0,
        ])->all();
    }

    /**
     * Despesas por veículo em cada mês ('Y-m') ou ano ('Y') do período, com meses vazios
     * preenchidos. Usada pelos relatórios de gastos mensais e anuais por veículo.
     *
     * @return array{vehicles: array<int, string>, buckets: list<string>, cells: array<string, array<int, int>>}
     */
    public function seriesByVehicle(ReportFilters $filters, string $format): array
    {
        $rows = $this->expenses($filters)
            ->selectRaw('vehicles.id as vehicle_id, vehicles.name, expenses.occurred_on as day, sum(expenses.amount_cents) as total')
            ->groupBy('vehicles.id', 'vehicles.name', 'expenses.occurred_on')
            ->get();

        $vehicles = [];
        $cells = [];

        foreach ($rows as $row) {
            $key = CarbonImmutable::parse($row->day)->format($format);
            $vehicles[(int) $row->vehicle_id] = $row->name;
            $cells[$key][(int) $row->vehicle_id] = ($cells[$key][(int) $row->vehicle_id] ?? 0) + (int) $row->total;
        }

        asort($vehicles);

        return [
            'vehicles' => $vehicles,
            'buckets' => $this->bucketKeys($filters->period, $format, array_keys($cells)),
            'cells' => $cells,
        ];
    }

    /**
     * Lista completa de despesas do filtro (mais recentes primeiro), já com nomes de veículo e categoria.
     *
     * @return list<array{id: int, occurred_on: string, vehicle: string, category: string, description: string, vendor: ?string, payment_method: ?string, amount_cents: int}>
     */
    public function expenseRows(ReportFilters $filters): array
    {
        return $this->expenses($filters)
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->orderByDesc('expenses.occurred_on')
            ->orderByDesc('expenses.id')
            ->get([
                'expenses.id', 'expenses.occurred_on', 'vehicles.name as vehicle', 'expense_categories.name as category',
                'expenses.description', 'expenses.vendor', 'expenses.payment_method', 'expenses.amount_cents',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'occurred_on' => CarbonImmutable::parse($row->occurred_on)->toDateString(),
                'vehicle' => $row->vehicle,
                'category' => $row->category,
                'description' => $row->description,
                'vendor' => $row->vendor,
                'payment_method' => $row->payment_method,
                'amount_cents' => (int) $row->amount_cents,
            ])
            ->all();
    }

    // ----------------------------------------------------- previsto vs pago

    /**
     * Previsto e pago de recorrências no período, sempre separados:
     * pago = ocorrências pagas (por `paid_at`); previsto = previsões em aberto (por vencimento).
     * Canceladas ficam fora dos dois lados.
     *
     * @return array{paid_cents: int, forecast_cents: int, months: list<array{month: string, paid_cents: int, forecast_cents: int}>}
     */
    public function forecastVsPaid(ReportFilters $filters): array
    {
        $from = $filters->period->from->toDateString();
        $to = $filters->period->to->toDateString();

        $occurrences = fn () => RecurringExpenseOccurrence::query()
            ->whereHas('recurringExpense', fn ($query) => $query
                ->whereIn('vehicle_id', $this->vehicles($filters)->select('vehicles.id'))
                ->when($filters->categoryId, fn ($query, $id) => $query->where('expense_category_id', $id)));

        $paid = $occurrences()->where('status', OccurrenceStatus::Paid)->whereBetween('paid_at', [$from, $to])->get(['paid_at', 'paid_amount_cents']);
        $forecast = $occurrences()->forecast()->whereBetween('due_date', [$from, $to])->get(['due_date', 'expected_amount_cents']);

        $months = [];

        foreach ($paid as $item) {
            $key = $item->paid_at->format('Y-m');
            $months[$key]['paid_cents'] = ($months[$key]['paid_cents'] ?? 0) + (int) $item->paid_amount_cents;
        }

        foreach ($forecast as $item) {
            $key = $item->due_date->format('Y-m');
            $months[$key]['forecast_cents'] = ($months[$key]['forecast_cents'] ?? 0) + (int) $item->expected_amount_cents;
        }

        $keys = $this->bucketKeys($filters->period, 'Y-m', array_keys($months));

        return [
            'paid_cents' => (int) $paid->sum('paid_amount_cents'),
            'forecast_cents' => (int) $forecast->sum('expected_amount_cents'),
            'months' => array_map(fn (string $key) => [
                'month' => $key,
                'paid_cents' => $months[$key]['paid_cents'] ?? 0,
                'forecast_cents' => $months[$key]['forecast_cents'] ?? 0,
            ], $keys),
        ];
    }

    // -------------------------------------------------- próximos vencimentos

    /**
     * Previsões de recorrências em aberto dentro de uma janela a partir de hoje, incluindo
     * as vencidas; ordenadas por vencimento. Não são despesas pagas.
     *
     * @return list<array{kind: string, id: int, description: string, vehicle_id: int, vehicle_name: ?string, due_date: string, expected_amount_cents: int, days_until_due: int, is_overdue: bool}>
     */
    public function upcomingDue(ReportFilters $filters, int $withinDays = 90, int $limit = 10, ?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();

        return RecurringExpenseOccurrence::query()
            ->forecast()
            ->whereDate('due_date', '<=', $today->addDays($withinDays)->toDateString())
            ->whereHas('recurringExpense', fn ($query) => $query
                ->where('active', true)
                ->whereIn('vehicle_id', $this->vehicles($filters)->select('vehicles.id'))
                ->when($filters->categoryId, fn ($query, $id) => $query->where('expense_category_id', $id)))
            ->with('recurringExpense.vehicle:id,name,deleted_at')
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (RecurringExpenseOccurrence $occurrence) => [
                'kind' => 'recurring',
                'id' => $occurrence->id,
                'description' => $occurrence->recurringExpense->description,
                'vehicle_id' => $occurrence->recurringExpense->vehicle_id,
                'vehicle_name' => $occurrence->recurringExpense->vehicle?->name,
                'due_date' => $occurrence->due_date->toDateString(),
                'expected_amount_cents' => $occurrence->expected_amount_cents,
                'days_until_due' => (int) $today->diffInDays($occurrence->due_date->startOfDay(), false),
                'is_overdue' => $occurrence->due_date->startOfDay()->lt($today),
            ])
            ->values()
            ->all();
    }

    // ------------------------------------------------- completude do histórico

    /**
     * Informa se o período consultado começa antes do acompanhamento dos veículos do filtro.
     * Nunca apresentar esse intervalo como se tivesse todos os gastos.
     *
     * @return array{is_complete: bool, has_data: bool, tracking_start_date: ?string, incomplete_vehicles: list<array{id: int, name: string, tracking_start_date: ?string}>}
     */
    public function completeness(ReportFilters $filters): array
    {
        $from = $filters->period->unbounded ? null : $filters->period->from;

        $incomplete = $this->vehicles($filters)->get(['id', 'name', 'tracking_start_date', 'acquisition_date'])
            ->filter(function (Vehicle $vehicle) use ($from): bool {
                if ($vehicle->tracking_start_date === null) {
                    return true;
                }

                // Sem recorte inferior, a pergunta é se o acompanhamento cobre desde a aquisição.
                $start = $from ?? CarbonImmutable::instance($vehicle->acquisition_date);

                return $vehicle->tracking_start_date->startOfDay()->gt($start);
            })
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'tracking_start_date' => $vehicle->tracking_start_date?->toDateString(),
            ])
            ->values();

        return [
            'is_complete' => $incomplete->isEmpty(),
            'has_data' => $this->expenses($filters)->exists(),
            'tracking_start_date' => $incomplete->pluck('tracking_start_date')->filter()->min(),
            'incomplete_vehicles' => $incomplete->all(),
        ];
    }

    // ------------------------------------------------------- por veículo (TCO)

    /**
     * Indicadores de propriedade de um veículo (ativo, vendido ou arquivado).
     *
     * - despesas operacionais: todas as despesas do veículo (vendido: aquisição → venda);
     * - TCO = compra + operacionais (compra não é despesa operacional);
     * - meses de propriedade = meses completos entre aquisição e venda/`asOf`, mínimo 1;
     * - custo por km = operacionais ÷ (odômetro atual ou de venda − odômetro de aquisição),
     *   só com distância positiva; senão `null` ("dados insuficientes");
     * - resultado da venda = venda − compra − operacionais, só para vendido com ambos os valores.
     *
     * @return array<string, mixed>
     */
    public function vehicleSummary(Vehicle $vehicle, ?CarbonInterface $asOf = null, ?int $categoryId = null): array
    {
        $asOf = CarbonImmutable::instance($asOf ?? now())->startOfDay();
        $sold = $vehicle->status === VehicleStatus::Sold;
        $end = $sold && $vehicle->sale_date ? CarbonImmutable::instance($vehicle->sale_date)->startOfDay() : $asOf;
        $start = CarbonImmutable::instance($vehicle->acquisition_date)->startOfDay();

        $operating = (int) $this->expenses(new ReportFilters(Period::all(), $vehicle->id, $categoryId))->sum('expenses.amount_cents');

        $purchase = $vehicle->purchase_price_cents;
        $months = max(1, (int) floor($start->diffInMonths($end)));
        $distance = $vehicle->acquisition_odometer !== null && $vehicle->odometer !== null
            ? $vehicle->odometer - $vehicle->acquisition_odometer
            : null;
        $distanceValid = $distance !== null && $distance > 0;

        $tco = $operating + ($purchase ?? 0);

        return [
            'vehicle_id' => $vehicle->id,
            'status' => $vehicle->status->value,
            'archived' => $vehicle->trashed(),
            'purchase_cents' => $purchase,
            'purchase_known' => $purchase !== null,
            'operating_cents' => $operating,
            'tco_cents' => $tco,
            'months_owned' => $months,
            'average_monthly_cost_cents' => (int) round($tco / $months),
            'average_monthly_operating_cents' => (int) round($operating / $months),
            'distance_km' => $distanceValid ? $distance : null,
            'cost_per_km_cents' => $distanceValid ? round($operating / $distance, 2) : null,
            'sale_price_cents' => $sold ? $vehicle->sale_price_cents : null,
            'sale_result_cents' => $sold && $vehicle->sale_price_cents !== null && $purchase !== null
                ? $vehicle->sale_price_cents - $purchase - $operating
                : null,
            'ownership_start' => $start->toDateString(),
            'ownership_end' => $sold ? $end->toDateString() : null,
            'tracking_start_date' => $vehicle->tracking_start_date?->toDateString(),
            'is_complete' => $vehicle->tracking_start_date !== null && $vehicle->tracking_start_date->startOfDay()->lte($start),
        ];
    }
}
