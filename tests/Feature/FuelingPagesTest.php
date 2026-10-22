<?php

namespace Tests\Feature;

use App\Models\Fueling;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FuelingPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function fuel(Vehicle $vehicle, string $date, int $odometer, float $liters, int $cents, bool $full = true): Fueling
    {
        return Fueling::factory()->create([
            'vehicle_id' => $vehicle->id,
            'fueled_on' => $date,
            'odometer' => $odometer,
            'liters' => $liters,
            'total_cents' => $cents,
            'price_per_liter' => round($cents / 100 / $liters, 3),
            'full_tank' => $full,
        ]);
    }

    public function test_empty_state_has_no_invented_numbers(): void
    {
        $this->get('/fuelings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Fuelings/Index')
                ->has('fuelings.data', 0)
                ->where('summary.count', 0)
                ->where('summary.average_km_per_liter', null)
                ->where('summary.average_price_cents_per_liter', null)
                ->where('summary.cost_per_km_cents', null)
                ->where('monthly', [])
                ->where('yearly', []));
    }

    public function test_rows_carry_consumption_only_when_computable(): void
    {
        $vehicle = Vehicle::factory()->create();
        $first = $this->fuel($vehicle, '2026-01-01', 10_000, 40, 24_000);
        $second = $this->fuel($vehicle, '2026-01-20', 10_400, 32, 19_200);
        $partial = $this->fuel($vehicle, '2026-02-05', 10_600, 10, 6_000, false);

        $this->get('/fuelings')->assertInertia(fn (Assert $page) => $page
            ->where('fuelings.data.0.id', $partial->id)
            ->where('fuelings.data.0.stats.km_per_liter', null)
            ->where('fuelings.data.1.id', $second->id)
            ->where('fuelings.data.1.stats.km_per_liter', 12.5)
            ->where('fuelings.data.1.stats.distance_km', 400)
            ->where('fuelings.data.2.id', $first->id)
            ->where('fuelings.data.2.stats.km_per_liter', null)
            ->where('fuelings.data.2.stats.reason', fn ($reason) => str_contains($reason, 'Primeiro tanque cheio')));
    }

    public function test_consumption_does_not_change_with_the_period_filter(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->fuel($vehicle, '2025-12-20', 9_600, 40, 24_000);
        $second = $this->fuel($vehicle, '2026-01-10', 10_000, 32, 19_200);

        $this->get('/fuelings?from=2026-01-01')->assertInertia(fn (Assert $page) => $page
            ->has('fuelings.data', 1)
            ->where('fuelings.data.0.id', $second->id)
            ->where('fuelings.data.0.stats.km_per_liter', 12.5)
            ->where('summary.average_km_per_liter', 12.5)
            ->where('summary.total_cents', 19_200));
    }

    public function test_monthly_and_yearly_totals_match_the_list(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->fuel($vehicle, '2025-12-31', 9_000, 30, 18_000);
        $this->fuel($vehicle, '2026-01-01', 9_400, 40, 24_000);
        $this->fuel($vehicle, '2026-01-31', 9_900, 20, 13_000);

        $this->get('/fuelings')->assertInertia(fn (Assert $page) => $page
            ->where('summary.total_cents', 55_000)
            ->where('summary.total_liters', 90)
            ->where('monthly.0.period', '2026-01')
            ->where('monthly.0.total_cents', 37_000)
            ->where('monthly.0.count', 2)
            ->where('monthly.1.period', '2025-12')
            ->where('yearly.0.period', '2026')
            ->where('yearly.0.total_cents', 37_000)
            ->where('yearly.1.total_cents', 18_000));
    }

    public function test_filters_by_vehicle_and_period_and_options_for_the_form(): void
    {
        $a = Vehicle::factory()->create(['odometer' => 5_000]);
        $b = Vehicle::factory()->create();
        $this->fuel($a, '2026-01-10', 5_100, 30, 18_000);
        $this->fuel($b, '2026-01-12', 8_100, 30, 18_000);
        $this->fuel($a, '2026-03-12', 5_900, 30, 18_000);

        $this->get("/fuelings?vehicle_id={$a->id}&from=2026-02-01&to=2026-12-31")->assertInertia(fn (Assert $page) => $page
            ->where('fuelings.total', 1)
            ->where('filters.vehicle_id', (string) $a->id)
            ->has('options.vehicles', 2)
            ->has('options.fuel_types', 8)
            ->where('options.vehicles.0.odometer', fn ($odometer) => is_int($odometer)));
    }

    public function test_paginates_by_twenty(): void
    {
        $vehicle = Vehicle::factory()->create();
        foreach (range(1, 22) as $i) {
            $this->fuel($vehicle, '2026-01-01', 1_000 + $i * 100, 30, 18_000);
        }

        $this->get('/fuelings')->assertInertia(fn (Assert $page) => $page
            ->has('fuelings.data', 20)
            ->where('fuelings.total', 22));
    }
}
