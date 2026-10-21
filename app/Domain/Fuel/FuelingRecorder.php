<?php

namespace App\Domain\Fuel;

use App\Models\Fueling;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

/**
 * Persiste abastecimentos completando o valor total (ou o preço por litro) e mantendo
 * `vehicles.odometer` igual ao maior odômetro conhecido.
 */
class FuelingRecorder
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Fueling
    {
        return DB::transaction(function () use ($data): Fueling {
            $fueling = Fueling::create($this->complete($data));
            $this->raiseVehicleOdometer($fueling);

            return $fueling;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Fueling $fueling, array $data): Fueling
    {
        return DB::transaction(function () use ($fueling, $data): Fueling {
            $fueling->update($this->complete($data));
            $this->raiseVehicleOdometer($fueling);

            return $fueling;
        });
    }

    /**
     * Total = litros × preço quando o total não foi informado (ajuste manual é preservado);
     * preço = total ÷ litros quando só o total foi informado.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function complete(array $data): array
    {
        unset($data['confirm_duplicate']);

        $liters = (float) $data['liters'];
        $hasPrice = isset($data['price_per_liter']) && $data['price_per_liter'] !== '';
        $hasTotal = isset($data['total_cents']) && $data['total_cents'] !== '';

        if (! $hasTotal && $hasPrice) {
            $data['total_cents'] = Fueling::totalCentsFor($liters, $data['price_per_liter']);
        } elseif ($hasTotal && ! $hasPrice) {
            $data['price_per_liter'] = round(((int) $data['total_cents']) / 100 / $liters, 3);
        }

        return $data;
    }

    private function raiseVehicleOdometer(Fueling $fueling): void
    {
        Vehicle::query()
            ->whereKey($fueling->vehicle_id)
            ->where('odometer', '<', $fueling->odometer)
            ->update(['odometer' => $fueling->odometer]);
    }
}
