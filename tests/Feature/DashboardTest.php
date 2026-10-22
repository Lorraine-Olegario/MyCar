<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dashboard_works_without_vehicles(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('vehicle', null));
    }

    public function test_dashboard_shows_latest_active_vehicle_and_ignores_sold_ones(): void
    {
        Vehicle::factory()->create(['status' => VehicleStatus::Sold, 'model' => 'Vendido']);
        $active = Vehicle::factory()->create(['status' => VehicleStatus::Active, 'model' => 'Onix']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('vehicle.id', $active->id));
    }
}
