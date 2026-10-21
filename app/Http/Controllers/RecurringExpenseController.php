<?php

namespace App\Http\Controllers;

use App\Domain\Recurring\RecurringExpenseService;
use App\Enums\RecurrenceFrequency;
use App\Http\Requests\PayOccurrenceRequest;
use App\Http\Requests\StoreRecurringExpenseRequest;
use App\Models\ExpenseCategory;
use App\Models\RecurringExpense;
use App\Models\RecurringExpenseOccurrence;
use App\Models\Vehicle;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RecurringExpenseController extends Controller
{
    public function __construct(private readonly RecurringExpenseService $service) {}

    public function index(): Response
    {
        $recurring = RecurringExpense::query()
            ->with(['category:id,name,color', 'vehicle:id,name,deleted_at'])
            ->withMin(['occurrences as next_due_date' => fn ($query) => $query->forecast()], 'due_date')
            ->orderByDesc('active')
            ->orderBy('description')
            ->get();

        $occurrences = RecurringExpenseOccurrence::query()
            ->whereIn('recurring_expense_id', $recurring->pluck('id'))
            ->orderBy('competence_date')
            ->get()
            ->groupBy('recurring_expense_id');

        return Inertia::render('Recurring/Index', [
            'recurring' => $recurring,
            'occurrences' => $occurrences,
            'upcoming' => $this->service->upcoming(),
            'summary' => $this->service->monthSummary(now()),
            'options' => [
                'vehicles' => Vehicle::query()->orderBy('name')->get(['id', 'name', 'brand', 'model', 'version'])
                    ->map(fn (Vehicle $vehicle) => ['value' => $vehicle->id, 'label' => $vehicle->full_name])->values(),
                'categories' => ExpenseCategory::query()->ordered()->get(['id', 'name', 'active'])
                    ->map(fn (ExpenseCategory $category) => [
                        'value' => $category->id,
                        'label' => $category->name,
                        'active' => $category->active,
                    ])->values(),
                'frequencies' => array_map(
                    fn (RecurrenceFrequency $frequency) => ['value' => $frequency->value, 'label' => $frequency->label()],
                    RecurrenceFrequency::cases(),
                ),
            ],
        ]);
    }

    public function store(StoreRecurringExpenseRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return to_route('recurring.index')->with('success', 'Despesa recorrente criada. As previsões dos próximos meses foram geradas.');
    }

    public function update(StoreRecurringExpenseRequest $request, RecurringExpense $recurring): RedirectResponse
    {
        $this->service->update($recurring, $request->validated());

        return to_route('recurring.index')->with('success', 'Despesa recorrente atualizada. Apenas as previsões futuras foram recalculadas.');
    }

    public function pay(PayOccurrenceRequest $request, RecurringExpenseOccurrence $occurrence): RedirectResponse
    {
        try {
            $this->service->confirmPayment($occurrence, $request->validated('paid_on'), (int) $request->validated('paid_amount_cents'));
        } catch (DomainException $exception) {
            return to_route('recurring.index')->with('error', $exception->getMessage());
        }

        return to_route('recurring.index')->with('success', 'Pagamento confirmado e despesa registrada.');
    }

    public function cancel(RecurringExpenseOccurrence $occurrence): RedirectResponse
    {
        try {
            $this->service->cancel($occurrence);
        } catch (DomainException $exception) {
            return to_route('recurring.index')->with('error', $exception->getMessage());
        }

        return to_route('recurring.index')->with('success', 'Previsão cancelada.');
    }
}
