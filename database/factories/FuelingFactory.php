<?php

namespace Database\Factories;

use App\Enums\FuelType;
use App\Models\Fueling;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fueling>
 */
class FuelingFactory extends Factory
{
    protected $model = Fueling::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $liters = fake()->randomFloat(3, 20, 50);
        $price = fake()->randomFloat(3, 4.5, 7.5);

        return [
            'vehicle_id' => Vehicle::factory(),
            'fueled_on' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'odometer' => fake()->numberBetween(1_000, 150_000),
            'liters' => $liters,
            'price_per_liter' => $price,
            'total_cents' => Fueling::totalCentsFor($liters, $price),
            'fuel_type' => FuelType::Gasoline,
            'station' => null,
            'full_tank' => true,
        ];
    }

    public function partial(): static
    {
        return $this->state(fn () => ['full_tank' => false]);
    }
}
