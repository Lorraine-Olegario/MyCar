<?php

namespace App\Http\Requests;

use App\Enums\FuelType;
use App\Models\Fueling;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFuelingRequest extends FormRequest
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
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'fueled_on' => ['required', 'date'],
            'odometer' => ['required', 'integer', 'min:0'],
            'liters' => ['required', 'numeric', 'gt:0', 'max:99999.999'],
            'price_per_liter' => ['nullable', 'numeric', 'gt:0', 'max:99999.999'],
            'total_cents' => ['nullable', 'integer', 'min:1'],
            'fuel_type' => ['required', Rule::enum(FuelType::class)],
            'station' => ['nullable', 'string', 'max:255'],
            'full_tank' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'confirm_duplicate' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Regras que dependem de outros registros: valor informado, odômetro coerente
     * com o histórico do veículo e duplicidade exata.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validatePricing($validator);
                $this->validateOdometer($validator);
                $this->validateDuplicate($validator);
            },
        ];
    }

    private function validatePricing(Validator $validator): void
    {
        if (! $this->filled('price_per_liter') && ! $this->filled('total_cents')) {
            $validator->errors()->add('price_per_liter', 'Informe o preço por litro ou o valor total.');
        }
    }

    private function validateOdometer(Validator $validator): void
    {
        $vehicleId = (int) $this->input('vehicle_id');
        $date = (string) $this->date('fueled_on')->toDateString();
        $odometer = (int) $this->input('odometer');

        $others = Fueling::query()
            ->where('vehicle_id', $vehicleId)
            ->when($this->route('fueling'), fn ($query, $fueling) => $query->whereKeyNot($fueling->getKey()));

        $previous = (clone $others)->whereDate('fueled_on', '<', $date)->max('odometer');
        $next = (clone $others)->whereDate('fueled_on', '>', $date)->min('odometer');

        if ($previous !== null && $odometer < (int) $previous) {
            $validator->errors()->add('odometer', 'O odômetro não pode ser menor que o de abastecimentos anteriores ('.number_format((int) $previous, 0, ',', '.').' km).');
        } elseif ($next !== null && $odometer > (int) $next) {
            $validator->errors()->add('odometer', 'O odômetro não pode ser maior que o do próximo abastecimento já registrado ('.number_format((int) $next, 0, ',', '.').' km).');
        }
    }

    private function validateDuplicate(Validator $validator): void
    {
        if ($validator->errors()->has('odometer') || $this->boolean('confirm_duplicate')) {
            return;
        }

        $duplicate = Fueling::query()
            ->where('vehicle_id', (int) $this->input('vehicle_id'))
            ->whereDate('fueled_on', (string) $this->date('fueled_on')->toDateString())
            ->where('odometer', (int) $this->input('odometer'))
            ->whereBetween('liters', [round((float) $this->input('liters'), 3) - 0.0005, round((float) $this->input('liters'), 3) + 0.0005])
            ->when($this->route('fueling'), fn ($query, $fueling) => $query->whereKeyNot($fueling->getKey()))
            ->exists();

        if ($duplicate) {
            $validator->errors()->add('duplicate', 'Já existe um abastecimento igual (mesmo veículo, data, odômetro e litros). Confirme para registrar mesmo assim.');
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'vehicle_id' => 'veículo',
            'fueled_on' => 'data',
            'odometer' => 'odômetro',
            'liters' => 'litros',
            'price_per_liter' => 'preço por litro',
            'total_cents' => 'valor total',
            'fuel_type' => 'combustível',
            'station' => 'posto',
            'full_tank' => 'tanque cheio',
            'notes' => 'observações',
        ];
    }
}
