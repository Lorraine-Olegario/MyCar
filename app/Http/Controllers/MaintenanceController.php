<?php

namespace App\Http\Controllers;

use App\Domain\Maintenance\MaintenanceDueEvaluator;
use App\Domain\Maintenance\MaintenanceService;
use App\Enums\MaintenanceType;
use App\Enums\PaymentMethod;
use App\Http\Requests\MaintenanceRequest;
use App\Models\ExpenseCategory;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function __construct(
        private readonly MaintenanceService $service,
        private readonly MaintenanceDueEvaluator $due,
    ) {}

    /**
     * Histórico de manutenções (mais recentes primeiro), com sinalização de vencimentos.
     * O aviso de "próxima troca" vale só para a manutenção mais recente de cada categoria
     * por veículo: as anteriores foram substituídas e viram apenas histórico.
     */
    public function index(Request $request): Response
    {
        $vehicleId = (string) $request->string('vehicle_id');

        $query = Maintenance::query()
            ->with(['expense.category:id,name,color', 'vehicle:id,name,odometer,deleted_at'])
            ->when($vehicleId !== '', fn ($query) => $query->where('maintenances.vehicle_id', $vehicleId))
            ->join('expenses', 'expenses.id', '=', 'maintenances.expense_id')
            ->select('maintenances.*')
            ->orderByDesc('expenses.occurred_on')
            ->orderByDesc('maintenances.id');

        $latestByCategory = Maintenance::query()
            ->join('expenses', 'expenses.id', '=', 'maintenances.expense_id')
            ->selectRaw('maintenances.vehicle_id, expenses.expense_category_id, max(expenses.occurred_on) as latest')
            ->groupBy('maintenances.vehicle_id', 'expenses.expense_category_id')
            ->get()
            ->mapWithKeys(fn ($row) => ["{$row->vehicle_id}:{$row->expense_category_id}" => substr((string) $row->latest, 0, 10)]);

        $maintenances = $query->paginate(20)->withQueryString()
            ->through(fn (Maintenance $maintenance) => $this->row($maintenance, $latestByCategory));

        $editing = $request->filled('edit')
            ? Maintenance::query()->with(['expense.category:id,name,color', 'vehicle:id,name,odometer,deleted_at'])->find($request->integer('edit'))
            : null;

        return Inertia::render('Maintenances/Index', [
            'maintenances' => $maintenances,
            'editing' => $editing ? $this->row($editing, $latestByCategory) : null,
            'filters' => ['vehicle_id' => $vehicleId],
            'options' => [
                'vehicles' => Vehicle::query()->orderBy('name')->get(['id', 'name', 'brand', 'model', 'version', 'odometer'])
                    ->map(fn (Vehicle $vehicle) => ['value' => $vehicle->id, 'label' => $vehicle->full_name, 'odometer' => $vehicle->odometer])->values(),
                'categories' => ExpenseCategory::query()->forMaintenance()->ordered()->get(['id', 'name', 'active'])
                    ->map(fn (ExpenseCategory $category) => ['value' => $category->id, 'label' => $category->name, 'active' => $category->active])->values(),
                'types' => array_map(fn (MaintenanceType $type) => ['value' => $type->value, 'label' => $type->label()], MaintenanceType::cases()),
                'payment_methods' => array_map(fn (PaymentMethod $method) => ['value' => $method->value, 'label' => $method->label()], PaymentMethod::cases()),
            ],
        ]);
    }

    /**
     * @param  Collection<string, string>  $latestByCategory
     * @return array<string, mixed>
     */
    private function row(Maintenance $maintenance, $latestByCategory): array
    {
        $expense = $maintenance->expense;
        $isCurrent = ($latestByCategory["{$maintenance->vehicle_id}:{$expense->expense_category_id}"] ?? null) === $expense->occurred_on->toDateString();

        return [
            ...Arr::only($maintenance->toArray(), ['id', 'vehicle_id', 'type', 'odometer', 'next_due_on', 'next_due_odometer', 'notes']),
            'expense' => Arr::only($expense->toArray(), ['id', 'description', 'amount_cents', 'occurred_on', 'expense_category_id', 'vendor', 'payment_method']),
            'category' => $expense->category?->only(['id', 'name', 'color']),
            'vehicle_name' => $maintenance->vehicle?->name,
            'due' => $isCurrent
                ? $this->due->evaluate($maintenance, $maintenance->vehicle?->odometer)
                : ['status' => 'none', 'days_remaining' => null, 'km_remaining' => null, 'reason' => null],
        ];
    }

    public function store(MaintenanceRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return to_route('maintenances.index')->with('success', 'Manutenção registrada com sucesso.');
    }

    public function update(MaintenanceRequest $request, Maintenance $maintenance): RedirectResponse
    {
        $this->service->update($maintenance, $request->validated());

        return to_route('maintenances.index')->with('success', 'Manutenção atualizada com sucesso.');
    }

    public function destroy(Maintenance $maintenance): RedirectResponse
    {
        $this->service->delete($maintenance);

        return to_route('maintenances.index')->with('success', 'Manutenção e despesa vinculada excluídas.');
    }
}
