<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'expense_category_id' => ExpenseCategory::factory(),
            'description' => fake()->randomElement(['Troca de óleo', 'Pneus novos', 'Lavagem completa', 'Estacionamento', 'Seguro']),
            'amount_cents' => fake()->numberBetween(1_000, 250_000),
            'occurred_on' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'odometer' => null,
            'vendor' => null,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'notes' => null,
        ];
    }
}
