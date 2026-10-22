<?php

namespace App\Http\Requests;

use App\Enums\RecurrenceFrequency;
use App\Models\ExpenseCategory;
use App\Models\RecurringExpense;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'end_date' => $this->filled('end_date') ? $this->input('end_date') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var RecurringExpense|null $recurring */
        $recurring = $this->route('recurring');

        return [
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'expense_category_id' => [
                'required',
                'integer',
                'exists:expense_categories,id',
                function (string $attribute, mixed $value, Closure $fail) use ($recurring): void {
                    $category = ExpenseCategory::query()->find($value);

                    if ($category && ! $category->active && $recurring?->expense_category_id !== $category->id) {
                        $fail('A categoria selecionada está inativa.');
                    }
                },
            ],
            'description' => ['required', 'string', 'max:255'],
            'expected_amount_cents' => ['required', 'integer', 'min:1'],
            'frequency' => ['required', Rule::enum(RecurrenceFrequency::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'vehicle_id' => 'veículo',
            'expense_category_id' => 'categoria',
            'description' => 'descrição',
            'expected_amount_cents' => 'valor previsto',
            'frequency' => 'frequência',
            'start_date' => 'início',
            'end_date' => 'fim',
            'due_day' => 'dia de vencimento',
            'notes' => 'observações',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expected_amount_cents.min' => 'O valor previsto deve ser maior que zero.',
            'end_date.after_or_equal' => 'O fim deve ser igual ou posterior ao início.',
            'due_day.between' => 'O dia de vencimento deve estar entre 1 e 31.',
        ];
    }
}
