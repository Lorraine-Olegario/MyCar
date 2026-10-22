<?php

namespace Database\Factories;

use App\Models\FipeQuote;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FipeQuote>
 */
class FipeQuoteFactory extends Factory
{
    protected $model = FipeQuote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'fipe_code' => '004001-0',
            'value_cents' => fake()->numberBetween(2_000_000, 12_000_000),
            'reference_month' => now()->format('Y-m'),
            'brand' => 'Chevrolet',
            'model' => 'Onix 1.0 LT',
            'model_year' => 2023,
            'fuel' => 'Flex',
            'consulted_at' => now(),
            'payload' => null,
        ];
    }
}
