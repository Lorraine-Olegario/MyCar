<?php

namespace Database\Factories;

use App\Enums\MaintenanceType;
use App\Models\Expense;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Maintenance>
 */
class MaintenanceFactory extends Factory
{
    protected $model = Maintenance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory(),
            'vehicle_id' => fn (array $attributes) => Expense::query()->findOrFail($attributes['expense_id'])->vehicle_id,
            'type' => MaintenanceType::Preventive,
            'odometer' => null,
            'next_due_on' => null,
            'next_due_odometer' => null,
        ];
    }
}
