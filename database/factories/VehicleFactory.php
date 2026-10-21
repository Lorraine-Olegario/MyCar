<?php

namespace Database\Factories;

use App\Enums\FuelType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * @var list<array{string, string, string, FuelType}>
     */
    private const CAR_CATALOG = [
        ['Chevrolet', 'Onix', '1.0 LT', FuelType::Flex],
        ['Chevrolet', 'Onix Plus', '1.0 LTZ', FuelType::Flex],
        ['Chevrolet', 'Tracker', '1.2 LT', FuelType::Flex],
        ['Fiat', 'Argo', '1.3 Drive', FuelType::Flex],
        ['Fiat', 'Strada', '1.4 Working', FuelType::Flex],
        ['Fiat', 'Uno', '1.0 Way', FuelType::Flex],
        ['Volkswagen', 'Gol', '1.0 MPI', FuelType::Flex],
        ['Volkswagen', 'Polo', '1.0 TSI', FuelType::Flex],
        ['Volkswagen', 'T-Cross', '200 TSI', FuelType::Flex],
        ['Toyota', 'Corolla', '2.0 XEi', FuelType::Flex],
        ['Toyota', 'Etios', '1.5 XS', FuelType::Flex],
        ['Honda', 'Civic', '2.0 EXL', FuelType::Gasoline],
        ['Honda', 'Fit', '1.5 LX', FuelType::Flex],
        ['Hyundai', 'HB20', '1.0 Vision', FuelType::Flex],
        ['Hyundai', 'Creta', '1.0 Comfort', FuelType::Flex],
        ['Renault', 'Kwid', '1.0 Zen', FuelType::Flex],
        ['Ford', 'Ka', '1.0 SE', FuelType::Flex],
        ['Jeep', 'Renegade', '1.3 T270', FuelType::Flex],
        ['Nissan', 'Kicks', '1.6 SV', FuelType::Flex],
        ['Toyota', 'Hilux', '2.8 SRX', FuelType::Diesel],
    ];

    /**
     * @var list<array{string, string, string, FuelType}>
     */
    private const MOTORCYCLE_CATALOG = [
        ['Honda', 'CG 160', 'Fan', FuelType::Gasoline],
        ['Honda', 'Biz 125', 'EX', FuelType::Gasoline],
        ['Yamaha', 'Factor 150', 'DX', FuelType::Gasoline],
    ];

    /**
     * @var list<string>
     */
    private const COLORS = [
        'Branco',
        'Preto',
        'Prata',
        'Cinza',
        'Vermelho',
        'Azul',
        'Verde',
        'Amarelo',
        'Marrom',
        'Bege',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isMotorcycle = fake()->boolean(20);

        [$brand, $model, $version, $fuel] = fake()->randomElement(
            $isMotorcycle ? self::MOTORCYCLE_CATALOG : self::CAR_CATALOG,
        );

        $manufactureYear = fake()->numberBetween(2010, (int) date('Y'));
        $odometer = fake()->numberBetween(0, 180000);
        $acquisitionDate = fake()->dateTimeBetween('-6 years', 'now');

        return [
            'name' => trim("{$brand} {$model} {$version}"),
            'brand' => $brand,
            'model' => $model,
            'version' => $version,
            'manufacture_year' => $manufactureYear,
            'model_year' => fake()->numberBetween($manufactureYear, $manufactureYear + 1),
            'category' => ($isMotorcycle ? VehicleCategory::Motorcycle : VehicleCategory::Car)->value,
            'plate' => fake()->boolean(85) ? fake()->unique()->regexify('[A-Z]{3}[0-9]{4}') : null,
            'color' => fake()->randomElement(self::COLORS),
            'fuel' => $fuel->value,
            'odometer' => $odometer,
            'acquisition_date' => $acquisitionDate->format('Y-m-d'),
            'acquisition_odometer' => fake()->boolean(70) ? fake()->numberBetween(0, max(0, $odometer)) : null,
            'purchase_price_cents' => fake()->numberBetween(2_000_000, 15_000_000),
            'sale_date' => null,
            'sale_price_cents' => null,
            'photo_path' => null,
            'notes' => fake()->boolean(40) ? fake()->sentence() : null,
            'status' => VehicleStatus::Active->value,
            'tracking_start_date' => fake()->dateTimeBetween($acquisitionDate, 'now')->format('Y-m-d'),
        ];
    }

    /**
     * Veículo vendido.
     */
    public function sold(): static
    {
        return $this->state(fn () => [
            'status' => VehicleStatus::Sold,
            'sale_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'sale_price_cents' => fake()->numberBetween(3_000_000, 16_000_000),
        ]);
    }

    /**
     * Veículo arquivado (soft deleted).
     */
    public function archived(): static
    {
        return $this->state(fn () => [
            'deleted_at' => now(),
        ]);
    }
}
