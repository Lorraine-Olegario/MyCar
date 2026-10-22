<?php

namespace Tests\Feature;

use App\Domain\Fipe\FipeException;
use App\Domain\Fipe\FipeQuoteService;
use App\Domain\Fipe\FipeSelection;
use App\Domain\Fipe\FipeVehicleType;
use App\Models\FipeQuote;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FipeServiceTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://fipe.test/api/v2/cars/brands/23/models/5940/years/2023-1';

    private Vehicle $vehicle;

    private FipeSelection $selection;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.fipe.base_url' => 'https://fipe.test/api/v2', 'services.fipe.retries' => 1, 'services.fipe.retry_sleep_ms' => 0, 'services.fipe.stale_days' => 35]);
        Cache::flush();
        Http::preventStrayRequests();
        Carbon::setTestNow('2026-03-10 09:30:00');

        $this->vehicle = Vehicle::factory()->create(['brand' => 'Chevrolet', 'model' => 'Onix', 'version' => '1.0 LT', 'model_year' => 2023]);
        $this->selection = new FipeSelection(FipeVehicleType::Cars, '23', '5940', '2023-1');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'brand' => 'Chevrolet', 'codeFipe' => '004001-0', 'fuel' => 'Flex', 'model' => 'Onix 1.0 LT 12V Flex 5p Mec.',
            'modelYear' => 2023, 'price' => 'R$ 68.450,90', 'referenceMonth' => 'março de 2026',
        ], $overrides);
    }

    private function service(): FipeQuoteService
    {
        return app(FipeQuoteService::class);
    }

    public function test_success_persists_history_and_updates_the_current_fields_atomically(): void
    {
        Http::fake([self::URL => Http::response($this->payload())]);

        $quote = $this->service()->consult($this->vehicle, $this->selection);

        $this->assertSame(6_845_090, $quote->value_cents);
        $this->assertSame('2026-03', $quote->reference_month);
        $this->assertSame('Onix 1.0 LT 12V Flex 5p Mec.', $quote->model);
        $this->assertSame('Flex', $quote->fuel);
        $this->assertSame(2023, $quote->model_year);
        $this->assertSame('R$ 68.450,90', $quote->payload['price']);
        $this->assertTrue($quote->consulted_at->equalTo(now()));

        $vehicle = $this->vehicle->fresh();
        $this->assertSame(6_845_090, $vehicle->fipe_value_cents);
        $this->assertSame('2026-03', $vehicle->fipe_reference_month);
        $this->assertSame('004001-0', $vehicle->fipe_code);
        $this->assertSame(['cars', '23', '5940', '2023-1'], [$vehicle->fipe_vehicle_type, $vehicle->fipe_brand_id, $vehicle->fipe_model_id, $vehicle->fipe_year_id]);
        $this->assertSame($quote->id, $vehicle->latestFipeQuote->id);
        $this->assertSame($vehicle->fipe_value_cents, $vehicle->latestFipeQuote->value_cents);
    }

    public function test_repeated_refresh_creates_new_history_and_the_current_value_is_the_newest(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push($this->payload())
            ->push($this->payload(['price' => 'R$ 67.000,00', 'referenceMonth' => 'abril de 2026']))]);

        $this->service()->consult($this->vehicle, $this->selection);
        Carbon::setTestNow('2026-04-12 10:00:00');
        $second = $this->service()->refresh($this->vehicle->fresh());

        $this->assertSame(2, FipeQuote::count());
        $vehicle = $this->vehicle->fresh();
        $this->assertSame(6_700_000, $vehicle->fipe_value_cents);
        $this->assertSame('2026-04', $vehicle->fipe_reference_month);
        $this->assertSame($second->id, $vehicle->latestFipeQuote->id);
        $this->assertSame([6_700_000, 6_845_090], $vehicle->fipeQuotes->pluck('value_cents')->all());
    }

    public function test_same_reference_month_still_creates_a_history_row(): void
    {
        Http::fake([self::URL => Http::response($this->payload())]);

        $this->service()->consult($this->vehicle, $this->selection);
        $this->service()->consult($this->vehicle->fresh(), $this->selection);

        $this->assertSame(2, FipeQuote::count());
    }

    public function test_mismatching_year_brand_model_or_version_is_rejected_without_writing(): void
    {
        $cases = [
            'ano' => ['modelYear' => 2020],
            'marca' => ['brand' => 'Fiat'],
            'modelo' => ['model' => 'Prisma 1.0 LT'],
            'versão' => ['model' => 'Onix 1.4 LTZ Flex'],
        ];

        foreach ($cases as $label => $override) {
            Http::fake([self::URL => Http::response($this->payload($override))]);

            try {
                $this->service()->consult($this->vehicle, $this->selection);
                $this->fail("Divergência de {$label} deveria ser rejeitada.");
            } catch (FipeException $exception) {
                $this->assertSame(FipeException::MISMATCH, $exception->reason, $label);
                $this->assertStringContainsString('não corresponde a este veículo', $exception->getMessage());
            }
        }

        $this->assertSame(0, FipeQuote::count());
        $this->assertNull($this->vehicle->fresh()->fipe_value_cents);
    }

    public function test_matching_ignores_case_accents_and_brand_prefix(): void
    {
        $vehicle = Vehicle::factory()->create(['brand' => 'Volkswagen', 'model' => 'Polo', 'version' => 'Comfortline', 'model_year' => 2023]);
        Http::fake([self::URL => Http::response($this->payload(['brand' => 'VW - VolksWagen', 'model' => 'POLO COMFORTLINE 200 TSI']))]);

        $this->assertSame(6_845_090, $this->service()->consult($vehicle, $this->selection)->value_cents);
    }

    public function test_vehicle_without_version_matches_on_model_name(): void
    {
        $vehicle = Vehicle::factory()->create(['brand' => 'Chevrolet', 'model' => 'Onix', 'version' => null, 'model_year' => 2023]);
        Http::fake([self::URL => Http::response($this->payload())]);

        $this->assertSame('004001-0', $this->service()->consult($vehicle, $this->selection)->fipe_code);
    }

    public function test_failure_keeps_the_previous_value_and_history_and_exposes_staleness(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push($this->payload())
            ->push('erro', 503)]);

        $first = $this->service()->consult($this->vehicle, $this->selection);
        Carbon::setTestNow('2026-05-01 08:00:00');

        try {
            $this->service()->refresh($this->vehicle->fresh());
            $this->fail('Deveria falhar.');
        } catch (FipeException $exception) {
            $this->assertSame(FipeException::UNAVAILABLE, $exception->reason);
        }

        $vehicle = $this->vehicle->fresh();
        $this->assertSame(1, FipeQuote::count());
        $this->assertSame(6_845_090, $vehicle->fipe_value_cents);
        $this->assertSame($first->id, $vehicle->latestFipeQuote->id);

        $snapshot = $this->service()->snapshot($vehicle);
        $this->assertSame(6_845_090, $snapshot['value_cents']);
        $this->assertTrue($snapshot['is_stale']);
        $this->assertSame('2026-03', $snapshot['reference_month']);
        $this->assertSame('2026-03-10', substr($snapshot['consulted_at'], 0, 10));
        $this->assertSame('Onix 1.0 LT 12V Flex 5p Mec.', $snapshot['model']);
    }

    public function test_snapshot_is_fresh_within_the_window_and_null_without_any_quote(): void
    {
        $this->assertNull($this->service()->snapshot($this->vehicle));

        Http::fake([self::URL => Http::response($this->payload())]);
        $this->service()->consult($this->vehicle, $this->selection);
        $vehicle = $this->vehicle->fresh();

        $this->assertFalse($this->service()->snapshot($vehicle)['is_stale']);
        $this->assertFalse($this->service()->snapshot($vehicle, Carbon::parse('2026-04-14'))['is_stale']);
        $this->assertTrue($this->service()->snapshot($vehicle, Carbon::parse('2026-04-15'))['is_stale']);
    }

    public function test_failure_on_first_consultation_creates_nothing_and_snapshot_stays_null(): void
    {
        Http::fake([self::URL => Http::response([], 404)]);

        try {
            $this->service()->consult($this->vehicle, $this->selection);
            $this->fail('Deveria falhar.');
        } catch (FipeException $exception) {
            $this->assertSame(FipeException::NOT_FOUND, $exception->reason);
        }

        $this->assertSame(0, FipeQuote::count());
        $this->assertNull($this->service()->snapshot($this->vehicle->fresh()));
    }

    public function test_database_failure_rolls_back_history_and_current_fields(): void
    {
        Http::fake([self::URL => Http::response($this->payload())]);
        DB::statement('create trigger fail_vehicle_update before update on vehicles begin select raise(abort, \'falha simulada\'); end');

        try {
            $this->service()->consult($this->vehicle, $this->selection);
            $this->fail('Deveria falhar.');
        } catch (QueryException) {
        }

        $this->assertSame(0, FipeQuote::count());
        $this->assertNull($this->vehicle->fresh()->fipe_value_cents);
    }

    public function test_refresh_without_a_stored_selection_is_rejected(): void
    {
        Http::fake();

        $this->expectException(FipeException::class);

        $this->service()->refresh($this->vehicle);
    }

    public function test_an_old_value_is_never_reused_as_current_after_a_new_success(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push($this->payload(['price' => 'R$ 70.000,00']))
            ->push($this->payload(['price' => 'R$ 65.000,00', 'referenceMonth' => 'abril de 2026']))]);

        $this->service()->consult($this->vehicle, $this->selection);
        Carbon::setTestNow('2026-04-10');
        $this->service()->refresh($this->vehicle->fresh());

        $this->assertSame(6_500_000, $this->service()->snapshot($this->vehicle->fresh())['value_cents']);
    }
}
