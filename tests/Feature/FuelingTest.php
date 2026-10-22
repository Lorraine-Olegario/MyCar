<?php

namespace Tests\Feature;

use App\Models\Fueling;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelingTest extends TestCase
{
    use RefreshDatabase;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vehicle = Vehicle::factory()->create(['odometer' => 10_000]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $this->vehicle->id,
            'fueled_on' => '2026-03-10',
            'odometer' => 10_500,
            'liters' => '40.000',
            'price_per_liter' => '5.899',
            'fuel_type' => 'gasoline',
            'full_tank' => true,
        ], $overrides);
    }

    public function test_total_is_calculated_from_liters_and_price(): void
    {
        $this->post('/fuelings', $this->payload())->assertRedirect('/fuelings')->assertSessionHasNoErrors();

        $fueling = Fueling::first();
        $this->assertSame(23_596, $fueling->total_cents);
        $this->assertSame('40.000', $fueling->liters);
        $this->assertSame('5.899', $fueling->price_per_liter);
    }

    public function test_manual_total_is_preserved_and_price_can_be_derived(): void
    {
        $this->post('/fuelings', $this->payload(['total_cents' => 23_500]))->assertSessionHasNoErrors();
        $this->assertSame(23_500, Fueling::first()->total_cents);

        $this->post('/fuelings', $this->payload(['odometer' => 11_000, 'price_per_liter' => null, 'total_cents' => 24_000]))
            ->assertSessionHasNoErrors();
        $this->assertSame('6.000', Fueling::orderByDesc('id')->first()->price_per_liter);
    }

    public function test_price_or_total_is_required(): void
    {
        $this->post('/fuelings', $this->payload(['price_per_liter' => null]))
            ->assertSessionHasErrors(['price_per_liter' => 'Informe o preço por litro ou o valor total.']);
    }

    public function test_basic_validation(): void
    {
        $this->post('/fuelings', [])->assertSessionHasErrors(['vehicle_id', 'fueled_on', 'odometer', 'liters', 'fuel_type', 'full_tank']);
        $this->post('/fuelings', $this->payload(['odometer' => -5]))->assertSessionHasErrors('odometer');
        $this->post('/fuelings', $this->payload(['liters' => 0]))->assertSessionHasErrors('liters');
        $this->post('/fuelings', $this->payload(['fuel_type' => 'agua']))->assertSessionHasErrors('fuel_type');
        $this->assertSame(0, Fueling::count());
    }

    public function test_vehicle_odometer_follows_the_highest_known_value(): void
    {
        $this->post('/fuelings', $this->payload(['odometer' => 10_800]));
        $this->assertSame(10_800, $this->vehicle->fresh()->odometer);

        $this->post('/fuelings', $this->payload(['fueled_on' => '2026-01-01', 'odometer' => 9_000, 'liters' => 30]));
        $this->assertSame(10_800, $this->vehicle->fresh()->odometer);
    }

    public function test_odometer_cannot_be_lower_than_a_previous_fueling(): void
    {
        Fueling::factory()->create(['vehicle_id' => $this->vehicle->id, 'fueled_on' => '2026-03-01', 'odometer' => 10_300]);

        $this->post('/fuelings', $this->payload(['odometer' => 10_200]))
            ->assertSessionHasErrors('odometer');
        $this->assertSame(1, Fueling::count());
    }

    public function test_odometer_cannot_be_higher_than_the_next_fueling(): void
    {
        Fueling::factory()->create(['vehicle_id' => $this->vehicle->id, 'fueled_on' => '2026-04-01', 'odometer' => 10_900]);

        $this->post('/fuelings', $this->payload(['odometer' => 11_000]))->assertSessionHasErrors('odometer');
        $this->post('/fuelings', $this->payload(['odometer' => 10_700]))->assertSessionHasNoErrors();
    }

    public function test_same_day_fuelings_are_allowed_in_any_order(): void
    {
        Fueling::factory()->create(['vehicle_id' => $this->vehicle->id, 'fueled_on' => '2026-03-10', 'odometer' => 10_600]);

        $this->post('/fuelings', $this->payload(['odometer' => 10_450, 'liters' => 12]))->assertSessionHasNoErrors();
        $this->assertSame(2, Fueling::count());
    }

    public function test_odometer_rule_is_per_vehicle(): void
    {
        $other = Vehicle::factory()->create();
        Fueling::factory()->create(['vehicle_id' => $other->id, 'fueled_on' => '2026-01-01', 'odometer' => 90_000]);

        $this->post('/fuelings', $this->payload())->assertSessionHasNoErrors();
    }

    public function test_exact_duplicate_requires_confirmation(): void
    {
        $this->post('/fuelings', $this->payload())->assertSessionHasNoErrors();

        $this->post('/fuelings', $this->payload())->assertSessionHasErrors('duplicate');
        $this->assertSame(1, Fueling::count());

        $this->post('/fuelings', $this->payload(['confirm_duplicate' => true]))->assertSessionHasNoErrors();
        $this->assertSame(2, Fueling::count());
    }

    public function test_update_ignores_itself_and_recomputes_total(): void
    {
        $this->post('/fuelings', $this->payload());
        $fueling = Fueling::first();

        $this->put("/fuelings/{$fueling->id}", $this->payload(['liters' => '41.000', 'price_per_liter' => '6.000']))
            ->assertRedirect('/fuelings')
            ->assertSessionHasNoErrors();

        $this->assertSame(24_600, $fueling->fresh()->total_cents);
    }

    public function test_fueling_can_be_deleted_and_vehicle_with_fuelings_cannot_be_force_deleted(): void
    {
        $fueling = Fueling::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->expectException(QueryException::class);
        $this->vehicle->forceDelete();

        $this->delete("/fuelings/{$fueling->id}")->assertRedirect('/fuelings');
    }

    public function test_archived_vehicle_cannot_receive_fuelings(): void
    {
        $this->vehicle->delete();

        $this->post('/fuelings', $this->payload())->assertSessionHasErrors('vehicle_id');
    }
}
