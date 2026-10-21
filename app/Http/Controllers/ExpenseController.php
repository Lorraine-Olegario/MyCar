<?php

namespace App\Http\Controllers;

use App\Domain\Expenses\ExpenseReceiptStorage;
use App\Enums\PaymentMethod;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    /**
     * Lista despesas com filtros (veículo, categoria, período, busca), 20 por página.
     * Período e totais usam sempre `occurred_on`, nunca `created_at`.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'vehicle_id' => (string) $request->string('vehicle_id'),
            'expense_category_id' => (string) $request->string('expense_category_id'),
            'from' => (string) $request->string('from'),
            'to' => (string) $request->string('to'),
            'search' => (string) $request->string('search')->trim(),
        ];

        $query = Expense::query()
            ->when($filters['vehicle_id'] !== '', fn ($query) => $query->where('vehicle_id', $filters['vehicle_id']))
            ->when($filters['expense_category_id'] !== '', fn ($query) => $query->where('expense_category_id', $filters['expense_category_id']))
            ->occurredBetween($filters['from'] ?: null, $filters['to'] ?: null)
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = '%'.$filters['search'].'%';

                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', $search)
                        ->orWhere('vendor', 'like', $search)
                        ->orWhere('notes', 'like', $search);
                });
            });

        $totalCents = (int) (clone $query)->sum('amount_cents');

        $expenses = $query
            ->with(['category:id,name,color', 'vehicle:id,name,brand,model,version,tracking_start_date,deleted_at'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $filters,
            'total_cents' => $totalCents,
            'options' => $this->options(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'vehicles' => Vehicle::query()->orderBy('name')->get(['id', 'name', 'brand', 'model', 'version', 'tracking_start_date'])
                ->map(fn (Vehicle $vehicle) => [
                    'value' => $vehicle->id,
                    'label' => $vehicle->full_name,
                    'tracking_start_date' => $vehicle->tracking_start_date?->toDateString(),
                ])
                ->values(),
            'categories' => ExpenseCategory::query()->ordered()->get(['id', 'name', 'color', 'active'])
                ->map(fn (ExpenseCategory $category) => [
                    'value' => $category->id,
                    'label' => $category->name,
                    'color' => $category->color,
                    'active' => $category->active,
                ])
                ->values(),
            'payment_methods' => array_map(
                fn (PaymentMethod $method) => ['value' => $method->value, 'label' => $method->label()],
                PaymentMethod::cases(),
            ),
        ];
    }

    public function store(StoreExpenseRequest $request, ExpenseReceiptStorage $receipts): RedirectResponse
    {
        $expense = Expense::create($request->safe()->except('receipt'));

        $receipt = $request->file('receipt');

        if ($receipt instanceof UploadedFile) {
            $expense->update(['receipt_path' => $receipts->put($expense, $receipt)]);
        }

        return to_route('expenses.index')->with('success', 'Despesa registrada com sucesso.');
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, ExpenseReceiptStorage $receipts): RedirectResponse
    {
        $expense->update($request->safe()->except(['receipt', 'remove_receipt']));

        $receipt = $request->file('receipt');

        if ($receipt instanceof UploadedFile) {
            $expense->update(['receipt_path' => $receipts->put($expense, $receipt)]);
        } elseif ($request->boolean('remove_receipt')) {
            $receipts->forget($expense);
            $expense->update(['receipt_path' => null]);
        }

        return to_route('expenses.index')->with('success', 'Despesa atualizada com sucesso.');
    }

    /**
     * Exclui a despesa e o comprovante em disco, sem deixar arquivo órfão.
     */
    public function destroy(Expense $expense, ExpenseReceiptStorage $receipts): RedirectResponse
    {
        if ($expense->maintenance()->exists()) {
            return to_route('expenses.index')->with('error', 'Esta despesa pertence a uma manutenção. Exclua-a pela página de manutenções.');
        }

        $receipts->forget($expense);
        $expense->delete();

        return to_route('expenses.index')->with('success', 'Despesa excluída com sucesso.');
    }

    /**
     * Serve o comprovante a partir do disco privado.
     */
    public function receipt(Request $request, Expense $expense, ExpenseReceiptStorage $receipts): StreamedResponse
    {
        abort_unless($receipts->exists($expense), 404);

        if ($request->boolean('download')) {
            return Storage::disk(ExpenseReceiptStorage::DISK)->download($expense->receipt_path);
        }

        return Storage::disk(ExpenseReceiptStorage::DISK)->response($expense->receipt_path);
    }
}
