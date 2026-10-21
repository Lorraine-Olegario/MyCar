<?php

namespace Tests\Feature;

use App\Enums\FuelType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VehicleFormTest extends TestCase
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
            'sale_date' => null,
            'sale_price_cents' => null,
            'tracking_start_date' => '2023-05-10',
            'status' => VehicleStatus::Active->value,
            'notes' => 'Veículo em bom estado.',
        ], $overrides);
    }

    public function test_create_page_renders_the_form_without_a_vehicle(): void
    {
        $this->get('/vehicles/create')->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Form')
            ->where('vehicle', null)
            ->has('options.statuses', 2)
            ->has('options.categories', 3)
            ->where('options.fuels', fn (Collection $fuels) => $fuels->firstWhere('value', 'flex')['label'] === 'Flex'));
    }

    public function test_edit_page_renders_the_form_with_the_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create([
            'name' => 'Fiat Argo 1.3 Drive',
            'brand' => 'Fiat',
            'model' => 'Argo',
            'version' => '1.3 Drive',
            'purchase_price_cents' => 6_500_000,
        ]);

        $this->get("/vehicles/{$vehicle->id}/edit")->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Form')
            ->where('vehicle.id', $vehicle->id)
            ->where('vehicle.full_name', 'Fiat Argo 1.3 Drive')
            ->where('vehicle.purchase_price_cents', 6_500_000)
            ->where('vehicle.photo_url', null)
            ->missing('vehicle.photo_path')
            ->has('options.categories'));
    }

    public function test_edit_page_returns_not_found_for_an_archived_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();
        $vehicle->delete();

        $this->get("/vehicles/{$vehicle->id}/edit")->assertNotFound();
    }

    public function test_store_accepts_the_form_payload_and_normalizes_the_plate(): void
    {
        $response = $this->post('/vehicles', $this->payload(['plate' => 'abc-1d23']));

        $vehicle = Vehicle::query()->firstOrFail();

        $response->assertRedirect(route('vehicles.show', $vehicle));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertSame('ABC1D23', $vehicle->plate);
        $this->assertSame(VehicleStatus::Active, $vehicle->status);
        $this->assertSame(7_500_000, $vehicle->purchase_price_cents);
    }

    public function test_update_from_the_form_saves_the_new_values(): void
    {
        $vehicle = Vehicle::factory()->create(['color' => 'Prata', 'odometer' => 10_000]);

        $response = $this->put("/vehicles/{$vehicle->id}", $this->payload([
            'color' => 'Preto',
            'odometer' => 42_000,
            'notes' => 'Atualizado pelo formulário.',
        ]));

        $response->assertRedirect(route('vehicles.show', $vehicle));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $vehicle->refresh();

        $this->assertSame('Preto', $vehicle->color);
        $this->assertSame(42_000, $vehicle->odometer);
        $this->assertSame('Atualizado pelo formulário.', $vehicle->notes);
    }

    public function test_validation_errors_are_shared_with_the_form(): void
    {
        $response = $this->from('/vehicles/create')->post('/vehicles', []);

        $response->assertRedirect('/vehicles/create');
        $response->assertSessionHasErrors([
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

    public function test_sale_data_are_required_when_the_form_marks_the_vehicle_as_sold(): void
    {
        $response = $this->from('/vehicles/create')->post('/vehicles', $this->payload([
            'status' => VehicleStatus::Sold->value,
            'sale_date' => null,
            'sale_price_cents' => null,
        ]));

        $response->assertRedirect('/vehicles/create');
        $response->assertSessionHasErrors(['sale_date', 'sale_price_cents']);
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_update_accepts_a_sold_vehicle_with_past_acquisition_data(): void
    {
        $vehicle = Vehicle::factory()->create([
            'acquisition_date' => '2019-03-04',
            'purchase_price_cents' => 4_000_000,
        ]);

        $response = $this->put("/vehicles/{$vehicle->id}", $this->payload([
            'acquisition_date' => '2019-03-04',
            'purchase_price_cents' => 4_000_000,
            'status' => VehicleStatus::Sold->value,
            'sale_date' => '2024-08-20',
            'sale_price_cents' => 5_600_000,
        ]));

        $response->assertSessionHasNoErrors();

        $vehicle->refresh();

        $this->assertSame(VehicleStatus::Sold, $vehicle->status);
        $this->assertSame('2019-03-04', $vehicle->acquisition_date?->format('Y-m-d'));
        $this->assertSame('2024-08-20', $vehicle->sale_date?->format('Y-m-d'));
    }

    public function test_edit_and_create_routes_are_reachable_from_the_pages(): void
    {
        $this->get('/vehicles')->assertInertia(fn (Assert $page) => $page->component('Vehicles/Index'));

        $vehicle = Vehicle::factory()->create();

        $this->get('/vehicles/create')->assertOk();
        $this->get("/vehicles/{$vehicle->id}/edit")->assertOk();
    }
}
