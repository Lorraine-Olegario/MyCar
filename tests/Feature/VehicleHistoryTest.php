<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VehicleHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_archived_vehicles_stay_out_of_the_default_list_and_appear_in_the_filter(): void
    {
        $active = Vehicle::factory()->create();
        $sold = Vehicle::factory()->sold()->create();
        $archived = Vehicle::factory()->create();
        $archived->delete();

        $this->get('/vehicles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.view', '')
                ->has('vehicles.data', 2)
                ->where('vehicles.data', fn ($data) => collect($data)->pluck('id')->sort()->values()->all()
                    === collect([$active->id, $sold->id])->sort()->values()->all()));

        $this->get('/vehicles?view=archived')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.view', 'archived')
                ->has('vehicles.data', 1)
                ->where('vehicles.data.0.id', $archived->id)
                ->where('vehicles.data.0.archived', true));
    }

    public function test_view_filter_separates_active_from_sold_and_counts_each_group(): void
    {
        Vehicle::factory()->count(2)->create();
        Vehicle::factory()->sold()->create();
        Vehicle::factory()->create()->delete();

        $this->get('/vehicles?view=sold')
            ->assertInertia(fn (Assert $page) => $page
                ->has('vehicles.data', 1)
                ->where('vehicles.data.0.status', 'sold')
                ->where('counts', ['active' => 2, 'sold' => 1, 'archived' => 1]));

        $this->get('/vehicles?view=active')
            ->assertInertia(fn (Assert $page) => $page->has('vehicles.data', 2));
    }

    public function test_archived_vehicle_can_be_restored_without_losing_data(): void
    {
        $vehicle = Vehicle::factory()->create(['notes' => 'Histórico importante']);
        $vehicle->delete();

        $this->patch("/vehicles/{$vehicle->id}/restore")->assertRedirect("/vehicles/{$vehicle->id}");

        $this->assertNull($vehicle->fresh()->deleted_at);
        $this->assertSame('Histórico importante', $vehicle->fresh()->notes);
        $this->get('/vehicles?view=archived')->assertInertia(fn (Assert $page) => $page->has('vehicles.data', 0));
    }

    public function test_sold_vehicle_remains_accessible_with_ownership_period(): void
    {
        $vehicle = Vehicle::factory()->sold()->create([
            'acquisition_date' => '2022-01-10',
            'sale_date' => '2024-04-20',
        ]);

        $this->get("/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('vehicle.status', 'sold')
                ->where('vehicle.ownership_label', '2 anos e 3 meses'));
    }

    public function test_ownership_label_covers_short_and_open_periods(): void
    {
        $short = Vehicle::factory()->make(['acquisition_date' => '2024-01-10', 'sale_date' => '2024-01-25']);
        $oneMonth = Vehicle::factory()->make(['acquisition_date' => '2023-01-10', 'sale_date' => '2024-02-10']);
        $noDate = Vehicle::factory()->make(['acquisition_date' => null, 'sale_date' => null]);

        $this->assertSame('menos de 1 mês', $short->ownership_label);
        $this->assertSame('1 ano e 1 mês', $oneMonth->ownership_label);
        $this->assertNull($noDate->ownership_label);
    }
}
