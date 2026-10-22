<?php

namespace Tests\Feature;

use App\Models\FipeQuote;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FipeQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_has_fipe_selection_and_current_quote_columns(): void
    {
        $vehicle = Vehicle::factory()->create();

        $vehicle->forceFill([
            'fipe_vehicle_type' => 'cars',
            'fipe_brand_id' => '23',
            'fipe_model_id' => '5940',
            'fipe_year_id' => '2023-1',
            'fipe_code' => '004001-0',
            'fipe_value_cents' => 6_845_090,
            'fipe_reference_month' => '2026-03',
            'fipe_consulted_at' => '2026-03-10 09:30:00',
        ])->save();

        $fresh = $vehicle->fresh();
        $this->assertSame('23', $fresh->fipe_brand_id);
        $this->assertSame('5940', $fresh->fipe_model_id);
        $this->assertSame('2023-1', $fresh->fipe_year_id);
        $this->assertSame(6_845_090, $fresh->fipe_value_cents);
        $this->assertSame('2026-03', $fresh->fipe_reference_month);
        $this->assertInstanceOf(Carbon::class, $fresh->fipe_consulted_at);
    }

    public function test_vehicles_start_without_any_fipe_data(): void
    {
        $vehicle = Vehicle::factory()->create()->fresh();

        $this->assertNull($vehicle->fipe_value_cents);
        $this->assertNull($vehicle->fipe_consulted_at);
        $this->assertNull($vehicle->latestFipeQuote);
        $this->assertCount(0, $vehicle->fipeQuotes);
    }

    public function test_each_consultation_is_kept_even_for_the_same_reference_month(): void
    {
        $vehicle = Vehicle::factory()->create();

        FipeQuote::factory()->count(3)->create(['vehicle_id' => $vehicle->id, 'reference_month' => '2026-03']);

        $this->assertSame(3, $vehicle->fipeQuotes()->count());
    }

    public function test_quotes_are_ordered_from_the_most_recent_consultation(): void
    {
        $vehicle = Vehicle::factory()->create();
        $old = FipeQuote::factory()->create(['vehicle_id' => $vehicle->id, 'consulted_at' => '2026-01-10 10:00:00', 'reference_month' => '2026-01']);
        $newest = FipeQuote::factory()->create(['vehicle_id' => $vehicle->id, 'consulted_at' => '2026-03-10 10:00:00', 'reference_month' => '2026-03']);
        $middle = FipeQuote::factory()->create(['vehicle_id' => $vehicle->id, 'consulted_at' => '2026-02-10 10:00:00', 'reference_month' => '2026-02']);

        $this->assertSame([$newest->id, $middle->id, $old->id], $vehicle->fipeQuotes->pluck('id')->all());
    }

    public function test_latest_quote_follows_the_last_persisted_consultation_even_if_inserted_out_of_order(): void
    {
        $vehicle = Vehicle::factory()->create();
        $latest = FipeQuote::factory()->create(['vehicle_id' => $vehicle->id, 'consulted_at' => '2026-03-10 10:00:00', 'value_cents' => 5_000_000]);
        FipeQuote::factory()->create(['vehicle_id' => $vehicle->id, 'consulted_at' => '2026-01-10 10:00:00', 'value_cents' => 9_000_000]);

        $this->assertSame($latest->id, $vehicle->fresh()->latestFipeQuote->id);
        $this->assertSame(5_000_000, $vehicle->fresh()->latestFipeQuote->value_cents);
    }

    public function test_same_timestamp_ties_break_by_id(): void
    {
        $vehicle = Vehicle::factory()->create();
        FipeQuote::factory()->create(['vehicle_id' => $vehicle->id, 'consulted_at' => '2026-03-10 10:00:00']);
        $second = FipeQuote::factory()->create(['vehicle_id' => $vehicle->id, 'consulted_at' => '2026-03-10 10:00:00']);

        $this->assertSame($second->id, $vehicle->fresh()->latestFipeQuote->id);
    }

    public function test_quotes_are_isolated_per_vehicle_and_payload_is_stored_as_json(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();
        FipeQuote::factory()->create(['vehicle_id' => $a->id, 'payload' => ['price' => 'R$ 1,00', 'priceHistory' => [1, 2]]]);

        $this->assertCount(1, $a->fipeQuotes);
        $this->assertCount(0, $b->fipeQuotes);
        $this->assertSame(['price' => 'R$ 1,00', 'priceHistory' => [1, 2]], $a->fipeQuotes->first()->payload);
    }

    public function test_quote_persists_all_descriptive_fields(): void
    {
        $quote = FipeQuote::factory()->create([
            'fipe_code' => '005340-6', 'brand' => 'Fiat', 'model' => 'Argo 1.3', 'model_year' => 2022,
            'fuel' => 'Gasolina', 'value_cents' => 7_000_000, 'reference_month' => '2026-02',
        ])->fresh();

        $this->assertSame('005340-6', $quote->fipe_code);
        $this->assertSame('Fiat', $quote->brand);
        $this->assertSame('Argo 1.3', $quote->model);
        $this->assertSame(2022, $quote->model_year);
        $this->assertSame('Gasolina', $quote->fuel);
        $this->assertSame('2026-02', $quote->reference_month);
        $this->assertNotNull($quote->vehicle);
    }

    public function test_quotes_survive_archiving_the_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();
        $quote = FipeQuote::factory()->create(['vehicle_id' => $vehicle->id]);

        $vehicle->delete();

        $this->assertModelExists($quote);
        $this->assertNotNull($quote->fresh()->vehicle);
    }
}
