<?php

namespace Database\Factories;

use App\Enums\RecurrenceFrequency;
use App\Models\ExpenseCategory;
use App\Models\RecurringExpense;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringExpense>
 */
class RecurringExpenseFactory extends Factory
{
    protected $model = RecurringExpense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'expense_category_id' => ExpenseCategory::factory(),
            'description' => fake()->randomElement(['Seguro', 'IPVA', 'Licenciamento', 'Estacionamento mensal']),
            'expected_amount_cents' => fake()->numberBetween(10_000, 300_000),
            'frequency' => RecurrenceFrequency::Monthly,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => null,
            'due_day' => 10,
            'active' => true,
        ];
    }

    public function frequency(RecurrenceFrequency $frequency): static
    {
        return $this->state(fn () => ['frequency' => $frequency]);
    }
}
