<?php

namespace App\Domain\Reporting;

use App\Domain\Fuel\FuelConsumptionCalculator;
use App\Domain\Maintenance\MaintenanceDueEvaluator;
use App\Domain\Maintenance\MaintenanceDueQueries;
use App\Enums\OccurrenceStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Fueling;
use App\Models\Maintenance;
use App\Models\RecurringExpense;
use App\Models\RecurringExpenseOccurrence;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

/**
 * Históricos da página do veículo. Monta apenas a aba pedida, com paginação (15 por
 * página) e filtros de período e categoria, sem duplicar regras: os números vêm do mesmo
 * domínio das demais telas.
 */
class VehicleHistory
{
    public const TABS = ['expenses', 'fuelings', 'maintenances', 'recurring', 'dues', 'fipe'];

    public const PER_PAGE = 15;

    public function __construct(
        private readonly FuelConsumptionCalculator $consumption,
        private readonly MaintenanceDueEvaluator $dueEvaluator,
        private readonly MaintenanceDueQueries $maintenanceDue,
        private readonly FinancialQueries $queries,
    ) {}

    /**
     * @param  array<string, mixed>  $input  tab, scope (all|selected|custom), from, to, category_id
     * @param  Period|null  $selected  período escolhido no bloco financeiro (ano ou mês)
     * @return array<string, mixed>
     */
    public function build(Vehicle $vehicle, array $input, ?Period $selected = null, ?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        $tab = in_array($input['tab'] ?? null, self::TABS, true) ? (string) $input['tab'] : 'expenses';
        [$period, $filters] = $this->resolvePeriod($input, $selected);
        $categoryId = isset($input['category_id']) && ctype_digit((string) $input['category_id']) ? (int) $input['category_id'] : null;
        $filters['category_id'] = $categoryId !== null ? (string) $categoryId : '';

        return [
            'tab' => $tab,
            'filters' => $filters,
            'data' => match ($tab) {
                'expenses' => $this->expenses($vehicle, $period, $categoryId),
                'fuelings' => $this->fuelings($vehicle, $period),
                'maintenances' => $this->maintenances($vehicle, $period, $categoryId, $today),
                'recurring' => $this->recurring($vehicle),
                'dues' => $this->dues($vehicle, $today),
                default => null,
            },
            'categories' => ExpenseCategory::query()->ordered()->get(['id', 'name'])
                ->map(fn (ExpenseCategory $category) => ['value' => $category->id, 'label' => $category->name])->values()->all(),
            'upcoming' => $this->upcoming($vehicle, $today),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: ?Period, 1: array{scope: string, from: string, to: string, category_id: string}}
     */
    private function resolvePeriod(array $input, ?Period $selected): array
    {
        $scope = (string) ($input['scope'] ?? 'all');

        if ($scope === 'selected' && $selected !== null) {
            return [$selected, ['scope' => 'selected', 'from' => '', 'to' => '', 'category_id' => '']];
        }

        if ($scope === 'custom') {
            $custom = Period::tryCustom(isset($input['from']) ? (string) $input['from'] : null, isset($input['to']) ? (string) $input['to'] : null);

            if ($custom !== null) {
                return [$custom, ['scope' => 'custom', 'from' => $custom->from->toDateString(), 'to' => $custom->to->toDateString(), 'category_id' => '']];
            }
        }

        return [null, ['scope' => 'all', 'from' => '', 'to' => '', 'category_id' => '']];
    }

    /**
     * @return array<string, mixed>
     */
    private function expenses(Vehicle $vehicle, ?Period $period, ?int $categoryId): array
    {
        $query = Expense::query()
            ->where('vehicle_id', $vehicle->id)
            ->when($categoryId, fn ($query, $id) => $query->where('expense_category_id', $id))
            ->when($period, fn ($query, Period $period) => $query->occurredBetween($period->from->toDateString(), $period->to->toDateString()));

        $total = (int) (clone $query)->sum('amount_cents');

        return [
            'total_cents' => $total,
            'items' => $query->with('category:id,name,color')
                ->withExists('maintenance')
                ->orderByDesc('occurred_on')
                ->orderByDesc('id')
                ->paginate(self::PER_PAGE, pageName: 'page')
                ->withQueryString()
                ->through(fn (Expense $expense) => [
                    ...Arr::only($expense->toArray(), ['id', 'description', 'amount_cents', 'occurred_on', 'vendor', 'has_receipt', 'recurring_expense_id']),
                    'category' => $expense->category?->only(['id', 'name', 'color']),
                    'is_maintenance' => (bool) $expense->maintenance_exists,
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fuelings(Vehicle $vehicle, ?Period $period): array
    {
        $all = Fueling::query()->where('vehicle_id', $vehicle->id)->get();
        $segments = $this->consumption->segments($all);

        $query = Fueling::query()
            ->where('vehicle_id', $vehicle->id)
            ->when($period, fn ($query, Period $period) => $query->whereBetween('fueled_on', [$period->from->toDateString(), $period->to->toDateString()]));

        $filtered = (clone $query)->get();
        $inFilter = $filtered->pluck('id')->flip();

        $summary = $this->consumption->summary($filtered);

        // Consumo agregado do recorte: só segmentos cujo tanque cheio fecha dentro do filtro.
        $distance = 0;
        $liters = 0.0;
        $cost = 0;

        foreach ($segments as $id => $segment) {
            if (isset($inFilter[$id]) && $segment['km_per_liter'] !== null) {
                $distance += $segment['distance_km'];
                $liters += $segment['liters'];
                $cost += $segment['cost_cents'];
            }
        }

        return [
            'summary' => [
                'count' => $summary['count'],
                'total_liters' => $summary['total_liters'],
                'total_cents' => $summary['total_cents'],
                'average_price_cents_per_liter' => $summary['average_price_cents_per_liter'],
                'average_km_per_liter' => $distance > 0 && $liters > 0 ? round($distance / $liters, 2) : null,
                'cost_per_km_cents' => $distance > 0 ? round($cost / $distance, 2) : null,
            ],
            'items' => $query->orderByDesc('fueled_on')->orderByDesc('odometer')->orderByDesc('id')
                ->paginate(self::PER_PAGE, pageName: 'page')
                ->withQueryString()
                ->through(fn (Fueling $fueling) => [...$fueling->toArray(), 'stats' => $segments[$fueling->id] ?? null]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function maintenances(Vehicle $vehicle, ?Period $period, ?int $categoryId, CarbonImmutable $today): array
    {
        $currentIds = $this->maintenanceDue->current([$vehicle->id])->pluck('id')->flip();

        $query = Maintenance::query()
            ->select('maintenances.*')
            ->join('expenses', 'expenses.id', '=', 'maintenances.expense_id')
            ->where('maintenances.vehicle_id', $vehicle->id)
            ->when($categoryId, fn ($query, $id) => $query->where('expenses.expense_category_id', $id))
            ->when($period, fn ($query, Period $period) => $query->whereBetween('expenses.occurred_on', [$period->from->toDateString(), $period->to->toDateString()]))
            ->with(['expense.category:id,name,color', 'vehicle:id,name,odometer,deleted_at'])
            ->orderByDesc('expenses.occurred_on')
            ->orderByDesc('maintenances.id');

        return [
            'items' => $query->paginate(self::PER_PAGE, pageName: 'page')->withQueryString()->through(function (Maintenance $maintenance) use ($currentIds, $vehicle, $today) {
                $due = isset($currentIds[$maintenance->id])
                    ? $this->dueEvaluator->evaluate($maintenance, $vehicle->odometer, $today)
                    : ['status' => 'none', 'days_remaining' => null, 'km_remaining' => null, 'reason' => null];

                return [
                    ...Arr::only($maintenance->toArray(), ['id', 'type', 'odometer', 'next_due_on', 'next_due_odometer', 'notes']),
                    'description' => $maintenance->expense->description,
                    'amount_cents' => $maintenance->expense->amount_cents,
                    'occurred_on' => $maintenance->expense->occurred_on->toDateString(),
                    'category' => $maintenance->expense->category?->only(['id', 'name', 'color']),
                    'due' => $due,
                ];
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recurring(Vehicle $vehicle): array
    {
        $models = RecurringExpense::query()
            ->where('vehicle_id', $vehicle->id)
            ->with('category:id,name,color')
            ->withMin(['occurrences as next_due_date' => fn ($query) => $query->forecast()], 'due_date')
            ->orderByDesc('active')
            ->orderBy('description')
            ->get();

        return ['items' => $models->map(fn (RecurringExpense $recurring) => [
            ...Arr::only($recurring->toArray(), ['id', 'description', 'expected_amount_cents', 'frequency', 'due_day', 'active', 'monthly_estimate_cents', 'start_date', 'end_date', 'next_due_date']),
            'category' => $recurring->category?->only(['id', 'name', 'color']),
        ])->values()->all()];
    }

    /**
     * Histórico de vencimentos: ocorrências de recorrências (previstas, pagas e canceladas)
     * e próximas trocas de manutenção, do mais recente ao mais antigo, com atrasos em destaque.
     *
     * @return array<string, mixed>
     */
    private function dues(Vehicle $vehicle, CarbonImmutable $today): array
    {
        $items = [];

        $occurrences = RecurringExpenseOccurrence::query()
            ->whereHas('recurringExpense', fn ($query) => $query->where('vehicle_id', $vehicle->id))
            ->with('recurringExpense:id,description')
            ->get();

        foreach ($occurrences as $occurrence) {
            $overdue = $occurrence->status === OccurrenceStatus::Forecast && $occurrence->due_date->startOfDay()->lt($today);

            $items[] = [
                'kind' => 'recurring',
                'key' => 'r'.$occurrence->id,
                'description' => $occurrence->recurringExpense->description,
                'due_date' => $occurrence->due_date->toDateString(),
                'due_odometer' => null,
                'amount_cents' => $occurrence->status === OccurrenceStatus::Paid ? $occurrence->paid_amount_cents : $occurrence->expected_amount_cents,
                'status' => $occurrence->status->value,
                'highlight' => $overdue ? 'overdue' : ($occurrence->status === OccurrenceStatus::Forecast && $occurrence->due_date->startOfDay()->lte($today->addDays(30)) ? 'due_soon' : null),
            ];
        }

        $currentIds = $this->maintenanceDue->current([$vehicle->id])->pluck('id')->flip();

        $maintenances = Maintenance::query()
            ->where('vehicle_id', $vehicle->id)
            ->where(fn ($query) => $query->whereNotNull('next_due_on')->orWhereNotNull('next_due_odometer'))
            ->with(['expense:id,description,occurred_on', 'vehicle:id,odometer,deleted_at'])
            ->get();

        foreach ($maintenances as $maintenance) {
            $due = isset($currentIds[$maintenance->id])
                ? $this->dueEvaluator->evaluate($maintenance, $vehicle->odometer, $today)
                : ['status' => 'none'];

            $items[] = [
                'kind' => 'maintenance',
                'key' => 'm'.$maintenance->id,
                'description' => $maintenance->expense->description,
                'due_date' => $maintenance->next_due_on?->toDateString(),
                'due_odometer' => $maintenance->next_due_odometer,
                'amount_cents' => null,
                'status' => isset($currentIds[$maintenance->id]) ? 'forecast' : 'superseded',
                'highlight' => in_array($due['status'], ['overdue', 'due_soon'], true) ? $due['status'] : null,
            ];
        }

        usort($items, fn (array $a, array $b) => [$b['due_date'] ?? '0000-00-00', $b['key']] <=> [$a['due_date'] ?? '0000-00-00', $a['key']]);

        $page = max(1, (int) request()->query('page', 1));
        $total = count($items);

        return [
            'items' => $this->paginate(array_slice($items, ($page - 1) * self::PER_PAGE, self::PER_PAGE), $total, $page),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function paginate(array $items, int $total, int $page): LengthAwarePaginator
    {
        return new \Illuminate\Pagination\LengthAwarePaginator($items, $total, self::PER_PAGE, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * Vencimentos em destaque (atrasados e próximos) do veículo, para o topo da seção.
     *
     * @return list<array<string, mixed>>
     */
    private function upcoming(Vehicle $vehicle, CarbonImmutable $today): array
    {
        $recurring = $this->queries->upcomingDue(new ReportFilters(Period::all(), $vehicle->id), withinDays: 90, limit: 10, today: $today);
        $maintenance = array_map(
            fn (array $item) => [...$item, 'expected_amount_cents' => null],
            $this->maintenanceDue->attention([$vehicle->id], $today),
        );

        $items = [...$recurring, ...$maintenance];
        usort($items, fn (array $a, array $b) => [$a['due_date'] ?? '9999-12-31', $a['kind'], $a['id']] <=> [$b['due_date'] ?? '9999-12-31', $b['kind'], $b['id']]);

        return $items;
    }
}
