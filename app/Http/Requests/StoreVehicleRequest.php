<?php

namespace App\Http\Requests;

use App\Enums\FuelType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $plate = $this->input('plate');

        if (is_string($plate)) {
            $plate = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate) ?? '');
        }

        $this->merge([
            'plate' => $plate === '' ? null : $plate,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxYear = (int) date('Y') + 1;
        $soldStatus = VehicleStatus::Sold->value;

        return [
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:255'],
            'manufacture_year' => ['required', 'integer', 'between:1900,'.$maxYear],
            'model_year' => ['required', 'integer', 'between:1900,'.$maxYear, 'gte:manufacture_year'],
            'category' => ['required', Rule::enum(VehicleCategory::class)],
            'plate' => [
                'nullable',
                'string',
                'regex:/^(?:[A-Z]{3}[0-9]{4}|[A-Z]{3}[0-9][A-Z][0-9]{2})$/',
                Rule::unique('vehicles', 'plate')->ignore($this->route('vehicle')),
            ],
            'color' => ['nullable', 'string', 'max:255'],
            'fuel' => ['required', Rule::enum(FuelType::class)],
            'odometer' => ['required', 'integer', 'min:0'],
            'acquisition_date' => ['required', 'date'],
            'acquisition_odometer' => ['nullable', 'integer', 'min:0'],
            'purchase_price_cents' => ['nullable', 'integer', 'min:0'],
            'sale_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
                'after_or_equal:acquisition_date',
                'required_if:status,'.$soldStatus,
            ],
            'sale_price_cents' => [
                'nullable',
                'integer',
                'min:0',
                'required_if:status,'.$soldStatus,
            ],
            'photo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096',
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'tracking_start_date' => ['nullable', 'date'],
        ];
    }
}
