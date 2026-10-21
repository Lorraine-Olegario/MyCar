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
        $expense = Expense::factory()->create();

        return [
            'vehicle_id' => $expense->vehicle_id,
            'expense_id' => $expense->id,
            'type' => MaintenanceType::Preventive,
            'odometer' => null,
            'next_due_on' => null,
            'next_due_odometer' => null,
        ];
    }
}
