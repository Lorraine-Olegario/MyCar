<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceType;
use App\Enums\PaymentMethod;
use App\Models\ExpenseCategory;
use App\Models\Maintenance;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['next_due_on', 'next_due_odometer', 'odometer'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Maintenance|null $maintenance */
        $maintenance = $this->route('maintenance');

        return [
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'expense_category_id' => [
                'required',
                'integer',
                'exists:expense_categories,id',
                function (string $attribute, mixed $value, Closure $fail) use ($maintenance): void {
                    $category = ExpenseCategory::query()->find($value);

                    if ($category === null) {
                        return;
                    }

                    if (! in_array($category->slug, ExpenseCategory::MAINTENANCE_SLUGS, true)) {
                        $fail('Escolha uma categoria de manutenção (óleo, preventiva, corretiva, pneus, freios, elétrica ou motor).');
                    } elseif (! $category->active && $maintenance?->expense?->expense_category_id !== $category->id) {
                        $fail('A categoria selecionada está inativa.');
                    }
                },
            ],
            'description' => ['required', 'string', 'max:255'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'occurred_on' => ['required', 'date'],
            'type' => ['required', Rule::enum(MaintenanceType::class)],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'next_due_on' => ['nullable', 'date', 'after:occurred_on'],
            'next_due_odometer' => ['nullable', 'integer', 'min:0', Rule::when($this->filled('odometer'), ['gt:odometer'])],
            'vendor' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
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
            'type' => 'tipo',
            'odometer' => 'odômetro',
            'next_due_on' => 'data da próxima troca',
            'next_due_odometer' => 'odômetro da próxima troca',
            'vendor' => 'fornecedor',
            'payment_method' => 'forma de pagamento',
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
            'next_due_on.after' => 'A próxima troca deve ser posterior à data da manutenção.',
            'next_due_odometer.gt' => 'O odômetro da próxima troca deve ser maior que o odômetro atual da manutenção.',
        ];
    }
}
