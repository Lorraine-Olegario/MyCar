<?php

namespace Tests\Feature;

use App\Enums\FuelType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VehicleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Chevrolet Onix 1.0 LT',
            'brand' => 'Chevrolet',
            'model' => 'Onix',
            'version' => '1.0 LT',
            'manufacture_year' => 2022,
            'model_year' => 2023,
            'category' => VehicleCategory::Car->value,
            'plate' => 'ABC1D23',
            'color' => 'Prata',
            'fuel' => FuelType::Flex->value,
            'odometer' => 35_000,
            'acquisition_date' => '2023-05-10',
            'acquisition_odometer' => 12_000,
            'purchase_price_cents' => 7_500_000,
            'notes' => 'Veículo em bom estado.',
            'status' => VehicleStatus::Active->value,
            'tracking_start_date' => '2023-05-10',
        ], $overrides);
    }

    public function test_active_vehicle_can_be_created(): void
    {
        $response = $this->post('/vehicles', $this->payload());

        $vehicle = Vehicle::query()->firstOrFail();

        $response->assertRedirect(route('vehicles.show', $vehicle));
        $response->assertSessionHas('success');

        $this->assertSame('Chevrolet', $vehicle->brand);
        $this->assertSame('ABC1D23', $vehicle->plate);
        $this->assertSame(VehicleStatus::Active, $vehicle->status);
        $this->assertSame(7_500_000, $vehicle->purchase_price_cents);
        $this->assertSame(35_000, $vehicle->odometer);
    }

    public function test_plate_can_be_omitted(): void
    {
        $this->post('/vehicles', $this->payload(['plate' => null]))
            ->assertSessionHasNoErrors();

        $this->assertNull(Vehicle::query()->firstOrFail()->plate);
    }

    public function test_sold_vehicle_can_be_created_without_expenses(): void
    {
        $response = $this->post('/vehicles', $this->payload([
            'status' => VehicleStatus::Sold->value,
            'sale_date' => '2024-06-01',
            'sale_price_cents' => 8_200_000,
        ]));

        $vehicle = Vehicle::query()->firstOrFail();

        $response->assertRedirect(route('vehicles.show', $vehicle));
        $this->assertTrue($vehicle->isSold());
        $this->assertSame('2024-06-01', $vehicle->sale_date->format('Y-m-d'));
        $this->assertSame(8_200_000, $vehicle->sale_price_cents);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->post('/vehicles', [])
            ->assertSessionHasErrors([
                'name',
                'brand',
                'model',
                'manufacture_year',
                'model_year',
                'category',
                'fuel',
                'odometer',
                'acquisition_date',
                'status',
            ]);
    }

    public function test_validation_messages_are_in_portuguese(): void
    {
        $this->post('/vehicles', ['name' => ''])
            ->assertSessionHasErrors(['name' => 'O campo nome é obrigatório.']);
    }

    public function test_years_must_be_between_1900_and_next_year(): void
    {
        $nextYear = (int) date('Y') + 1;

        $this->post('/vehicles', $this->payload(['manufacture_year' => 1899]))
            ->assertSessionHasErrors('manufacture_year');

        $this->post('/vehicles', $this->payload([
            'manufacture_year' => $nextYear + 1,
            'model_year' => $nextYear + 1,
        ]))->assertSessionHasErrors('manufacture_year');

        $this->post('/vehicles', $this->payload([
            'manufacture_year' => $nextYear,
            'model_year' => $nextYear,
        ]))->assertSessionHasNoErrors();
    }

    public function test_model_year_must_be_greater_than_or_equal_to_manufacture_year(): void
    {
        $this->post('/vehicles', $this->payload([
            'manufacture_year' => 2023,
            'model_year' => 2022,
        ]))->assertSessionHasErrors([
            'model_year' => 'O ano do modelo deve ser maior ou igual ao ano de fabricação.',
        ]);
    }

    public function test_plate_must_follow_old_or_mercosul_format(): void
    {
        foreach (['ABC-12', 'AB12345', 'ABCD1D23'] as $plate) {
            $this->post('/vehicles', $this->payload(['plate' => $plate]))
                ->assertSessionHasErrors('plate');
        }

        $this->post('/vehicles', $this->payload(['plate' => 'ABC-1234']))
            ->assertSessionHasNoErrors();

        $this->assertSame('ABC1234', Vehicle::query()->firstOrFail()->plate);
    }

    public function test_plate_must_be_unique(): void
    {
        Vehicle::factory()->create(['plate' => 'ABC1234']);

        $this->post('/vehicles', $this->payload(['plate' => 'abc-1234']))
            ->assertSessionHasErrors('plate');
    }

    public function test_odometer_and_prices_cannot_be_negative(): void
    {
        $this->post('/vehicles', $this->payload([
            'odometer' => -1,
            'acquisition_odometer' => -5,
            'purchase_price_cents' => -100,
            'sale_price_cents' => -1,
        ]))->assertSessionHasErrors([
            'odometer',
            'acquisition_odometer',
            'purchase_price_cents',
            'sale_price_cents',
        ]);
    }

    public function test_sale_date_cannot_be_in_the_future(): void
    {
        $this->post('/vehicles', $this->payload([
            'status' => VehicleStatus::Sold->value,
            'sale_date' => now()->addDay()->toDateString(),
            'sale_price_cents' => 8_000_000,
        ]))->assertSessionHasErrors('sale_date');
    }

    public function test_sale_date_cannot_be_before_acquisition(): void
    {
        $this->post('/vehicles', $this->payload([
            'acquisition_date' => '2024-01-10',
            'status' => VehicleStatus::Sold->value,
            'sale_date' => '2023-12-01',
            'sale_price_cents' => 8_000_000,
        ]))->assertSessionHasErrors('sale_date');
    }

    public function test_sold_status_requires_sale_date_and_price(): void
    {
        $this->post('/vehicles', $this->payload([
            'status' => VehicleStatus::Sold->value,
        ]))->assertSessionHasErrors(['sale_date', 'sale_price_cents']);
    }

    public function test_vehicle_can_be_updated_and_unique_plate_ignores_itself(): void
    {
        $vehicle = Vehicle::factory()->create(['plate' => 'ABC1D23']);

        $response = $this->put("/vehicles/{$vehicle->id}", $this->payload([
            'plate' => 'ABC1D23',
            'color' => 'Preto',
            'odometer' => 40_000,
        ]));

        $response->assertRedirect(route('vehicles.show', $vehicle));

        $vehicle->refresh();

        $this->assertSame('Preto', $vehicle->color);
        $this->assertSame(40_000, $vehicle->odometer);
    }

    public function test_update_rejects_plate_from_another_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['plate' => 'ABC1D23']);
        Vehicle::factory()->create(['plate' => 'XYZ9876']);

        $this->put("/vehicles/{$vehicle->id}", $this->payload(['plate' => 'XYZ9876']))
            ->assertSessionHasErrors('plate');
    }

    public function test_vehicle_can_be_archived_and_restored(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->delete("/vehicles/{$vehicle->id}")
            ->assertRedirect(route('vehicles.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($vehicle);
        $this->assertNull(Vehicle::query()->find($vehicle->id));

        $this->patch("/vehicles/{$vehicle->id}/restore")
            ->assertRedirect(route('vehicles.show', $vehicle));

        $this->assertNotNull(Vehicle::query()->find($vehicle->id));
    }

    public function test_archived_vehicle_is_not_reachable_by_default(): void
    {
        $vehicle = Vehicle::factory()->create();
        $vehicle->delete();

        $this->get("/vehicles/{$vehicle->id}")->assertNotFound();
    }

    public function test_vehicle_detail_is_rendered(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->get("/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Vehicles/Show')
                ->where('vehicle.id', $vehicle->id));
    }

    public function test_index_filters_by_search_status_category_and_archived(): void
    {
        $onix = Vehicle::factory()->create([
            'name' => 'Chevrolet Onix LT',
            'brand' => 'Chevrolet',
            'model' => 'Onix',
            'plate' => 'ABC1D23',
            'category' => VehicleCategory::Car,
            'status' => VehicleStatus::Active,
        ]);

        $moto = Vehicle::factory()->create([
            'name' => 'Honda CG 160',
            'brand' => 'Honda',
            'model' => 'CG 160',
            'plate' => 'MOT1234',
            'category' => VehicleCategory::Motorcycle,
            'status' => VehicleStatus::Sold,
            'sale_date' => '2024-01-01',
            'sale_price_cents' => 1_500_000,
        ]);

        $archived = Vehicle::factory()->create([
            'name' => 'Fiat Uno',
            'brand' => 'Fiat',
            'model' => 'Uno',
        ]);
        $archived->delete();

        $this->get('/vehicles?search=ABC1D23')->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Index')
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.id', $onix->id));

        $this->get('/vehicles?search=Honda')->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.id', $moto->id));

        $this->get('/vehicles?status=sold')->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.id', $moto->id));

        $this->get('/vehicles?category=motorcycle')->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.id', $moto->id));

        $this->get('/vehicles?archived=1')->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.id', $archived->id));

        $this->get('/vehicles')->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 2));
    }

    public function test_index_paginates_twelve_per_page(): void
    {
        Vehicle::factory()->count(13)->create();

        $this->get('/vehicles')->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Index')
            ->has('vehicles.data', 12)
            ->where('vehicles.total', 13)
            ->where('vehicles.last_page', 2));

        $this->get('/vehicles?page=2')->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1));
    }

    public function test_historical_vehicle_from_2023_is_preserved(): void
    {
        $response = $this->post('/vehicles', $this->payload([
            'acquisition_date' => '2023-03-01',
            'tracking_start_date' => '2023-03-01',
            'odometer' => 20_000,
        ]));

        $vehicle = Vehicle::query()->firstOrFail();

        $response->assertRedirect(route('vehicles.show', $vehicle));
        $this->assertSame('2023-03-01', $vehicle->acquisition_date->format('Y-m-d'));
        $this->assertSame('2023-03-01', $vehicle->tracking_start_date->format('Y-m-d'));
        $this->assertSame(20_000, $vehicle->odometer);
    }
}
