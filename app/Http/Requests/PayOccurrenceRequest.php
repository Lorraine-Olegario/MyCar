<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayOccurrenceRequest extends FormRequest
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
        return [
            'paid_on' => ['required', 'date'],
            'paid_amount_cents' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'paid_on' => 'data do pagamento',
            'paid_amount_cents' => 'valor pago',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paid_amount_cents.min' => 'O valor pago deve ser maior que zero.',
        ];
    }
}
