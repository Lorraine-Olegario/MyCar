<?php

namespace Tests\Feature;

use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VehiclePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_index_page_renders_the_list_with_pagination_props(): void
    {
        Vehicle::factory()->count(3)->create();

        $this->get('/vehicles')->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Index')
            ->has('vehicles.data', 3)
            ->has('vehicles.links')
            ->where('vehicles.total', 3)
            ->where('vehicles.per_page', 12)
            ->where('vehicles.current_page', 1)
            ->where('filters.search', '')
            ->where('filters.status', '')
            ->where('filters.category', '')
            ->where('filters.archived', false));
    }

    public function test_index_page_sends_enum_options_with_portuguese_labels(): void
    {
        $this->get('/vehicles')->assertInertia(fn (Assert $page) => $page
            ->where('options.statuses', [
                ['value' => 'active', 'label' => 'Ativo'],
                ['value' => 'sold', 'label' => 'Vendido'],
            ])
            ->where('options.categories', [
                ['value' => 'car', 'label' => 'Carro'],
                ['value' => 'motorcycle', 'label' => 'Moto'],
                ['value' => 'other', 'label' => 'Outro'],
            ])
            ->where('options.fuels', fn (Collection $fuels) => $fuels->firstWhere('value', 'flex')['label'] === 'Flex'
                && $fuels->contains(fn (array $fuel) => $fuel['value'] === 'cng' && $fuel['label'] === 'GNV')));
    }

    public function test_index_page_echoes_the_filters(): void
    {
        $archived = Vehicle::factory()->sold()->create([
            'name' => 'Chevrolet Onix 1.0 LT',
            'brand' => 'Chevrolet',
            'model' => 'Onix',
            'category' => VehicleCategory::Car,
        ]);
        $archived->delete();

        Vehicle::factory()->create(['brand' => 'Fiat', 'model' => 'Argo']);

        $this->get('/vehicles?search=Onix&status=sold&category=car&archived=1')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'Onix')
                ->where('filters.status', 'sold')
                ->where('filters.category', 'car')
                ->where('filters.archived', true)
                ->has('vehicles.data', 1)
                ->where('vehicles.data.0.id', $archived->id));
    }

    public function test_index_page_paginates_twelve_per_page_and_keeps_the_query_string(): void
    {
        Vehicle::factory()->count(13)->create();
        Vehicle::factory()->sold()->create();

        $this->get('/vehicles?status=active')->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 12)
            ->where('vehicles.total', 13)
            ->where('vehicles.last_page', 2)
            ->where('vehicles.links', fn (Collection $links) => $links->count() > 3
                && $links->contains(fn (array $link) => is_string($link['url'])
                    && str_contains($link['url'], 'status=active'))));

        $this->get('/vehicles?status=active&page=2')->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.current_page', 2));
    }

    public function test_index_page_returns_an_empty_list_when_nothing_matches_the_filters(): void
    {
        Vehicle::factory()->create(['brand' => 'Fiat', 'model' => 'Argo']);

        $this->get('/vehicles?search=inexistente')->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 0)
            ->where('vehicles.total', 0));
    }

    public function test_index_page_sends_photo_url_and_archived_flag_without_exposing_the_private_path(): void
    {
        $withPhoto = Vehicle::factory()->create(['photo_path' => 'vehicles/1/foto.jpg']);
        $archived = Vehicle::factory()->create();
        $archived->delete();

        $this->get('/vehicles')->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.id', $withPhoto->id)
            ->where('vehicles.data.0.archived', false)
            ->where('vehicles.data.0.photo_url', route('vehicles.photo', $withPhoto))
            ->missing('vehicles.data.0.photo_path'));

        $this->get('/vehicles?archived=1')->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.id', $archived->id)
            ->where('vehicles.data.0.archived', true)
            ->where('vehicles.data.0.photo_url', null)
            ->missing('vehicles.data.0.photo_path'));
    }

    public function test_show_page_renders_the_vehicle_details(): void
    {
        $vehicle = Vehicle::factory()->create([
            'brand' => 'Fiat',
            'model' => 'Argo',
            'version' => '1.3 Drive',
            'category' => VehicleCategory::Car,
            'status' => VehicleStatus::Active,
            'purchase_price_cents' => 6_500_000,
            'sale_date' => null,
            'sale_price_cents' => null,
        ]);

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Show')
            ->where('vehicle.id', $vehicle->id)
            ->where('vehicle.full_name', 'Fiat Argo 1.3 Drive')
            ->where('vehicle.category', 'car')
            ->where('vehicle.status', 'active')
            ->where('vehicle.purchase_price_cents', 6_500_000)
            ->where('vehicle.sale_result_cents', null)
            ->where('vehicle.photo_url', null)
            ->where('options.categories', fn (Collection $categories) => $categories->firstWhere('value', 'car')['label'] === 'Carro'));
    }

    public function test_show_page_computes_the_sale_result_when_purchase_and_sale_exist(): void
    {
        $vehicle = Vehicle::factory()->create([
            'purchase_price_cents' => 5_000_000,
            'sale_price_cents' => 6_200_000,
            'sale_date' => '2024-05-01',
            'status' => VehicleStatus::Sold,
        ]);

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Show')
            ->where('vehicle.sale_result_cents', 1_200_000)
            ->where('vehicle.sale_price_cents', 6_200_000)
            // As datas chegam em ISO; o frontend lê só os 10 primeiros caracteres
            // (ver formatDate) para não deslocar o dia pelo fuso.
            ->where('vehicle.sale_date', fn (string $date) => str_starts_with($date, '2024-05-01')));
    }

    public function test_show_page_omits_the_sale_result_without_a_purchase_price(): void
    {
        $vehicle = Vehicle::factory()->create([
            'purchase_price_cents' => null,
            'sale_price_cents' => 6_200_000,
            'sale_date' => '2024-05-01',
            'status' => VehicleStatus::Sold,
        ]);

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->where('vehicle.purchase_price_cents', null)
            ->where('vehicle.sale_result_cents', null));
    }

    public function test_index_page_orders_by_most_recent_first(): void
    {
        $older = Vehicle::factory()->create(['name' => 'Veículo antigo']);
        $newer = Vehicle::factory()->create(['name' => 'Veículo novo']);

        $this->get('/vehicles')->assertInertia(fn (Assert $page) => $page
            ->where('vehicles.data.0.id', $newer->id)
            ->where('vehicles.data.1.id', $older->id));
    }
}
