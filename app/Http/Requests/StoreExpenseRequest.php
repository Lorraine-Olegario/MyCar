<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Expense|null $expense */
        $expense = $this->route('expense');

        return [
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'expense_category_id' => [
                'required',
                'integer',
                'exists:expense_categories,id',
                // Categoria inativa só é aceita se for a que a despesa já usa (registros antigos).
                function (string $attribute, mixed $value, Closure $fail) use ($expense): void {
                    $category = ExpenseCategory::query()->find($value);

                    if ($category && ! $category->active && $expense?->expense_category_id !== $category->id) {
                        $fail('A categoria selecionada está inativa.');
                    }
                },
            ],
            'description' => ['required', 'string', 'max:255'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'occurred_on' => ['required', 'date'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'receipt' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                'max:5120',
            ],
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
            'amount_cents' => 'valor',
            'occurred_on' => 'data',
            'odometer' => 'odômetro',
            'vendor' => 'fornecedor',
            'payment_method' => 'forma de pagamento',
            'receipt' => 'comprovante',
            'notes' => 'observações',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount_cents.min' => 'O valor deve ser maior que zero.',
            'vehicle_id.exists' => 'Selecione um veículo válido e não arquivado.',
        ];
    }
}
