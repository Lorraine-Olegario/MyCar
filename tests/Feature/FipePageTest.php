<?php

namespace Tests\Feature;

use App\Models\FipeQuote;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FipePageTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://fipe.test/api/v2';

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['services.fipe.base_url' => self::BASE, 'services.fipe.retries' => 1, 'services.fipe.retry_sleep_ms' => 0]);
        Cache::flush();
        Http::preventStrayRequests();
        Carbon::setTestNow('2026-03-10 09:30:00');

        $this->vehicle = Vehicle::factory()->create(['category' => 'car', 'brand' => 'Chevrolet', 'model' => 'Onix', 'version' => '1.0 LT', 'model_year' => 2023]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function quote(array $overrides = []): array
    {
        return array_merge([
            'brand' => 'Chevrolet', 'codeFipe' => '004001-0', 'fuel' => 'Flex', 'model' => 'Onix 1.0 LT 12V Flex',
            'modelYear' => 2023, 'price' => 'R$ 68.450,90', 'referenceMonth' => 'março de 2026',
        ], $overrides);
    }

    private function consult(array $overrides = [])
    {
        return $this->post("/vehicles/{$this->vehicle->id}/fipe", array_merge(['type' => 'cars', 'brand_id' => '23', 'model_id' => '5940', 'year_id' => '2023-1'], $overrides));
    }

    public function test_page_props_for_a_vehicle_never_consulted(): void
    {
        $this->get("/vehicles/{$this->vehicle->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('fipe.snapshot', null)
            ->where('fipe.quotes', [])
            ->where('fipe.selection', null)
            ->where('fipe.vehicle_type', 'cars')
            ->where('fipe.needs_type', false)
            ->has('fipe.types', 3));
    }

    public function test_category_other_needs_the_user_to_choose_the_type(): void
    {
        $other = Vehicle::factory()->create(['category' => 'other']);

        $this->get("/vehicles/{$other->id}")->assertInertia(fn (Assert $page) => $page
            ->where('fipe.vehicle_type', null)
            ->where('fipe.needs_type', true));
    }

    public function test_motorcycle_maps_to_the_motorcycles_type(): void
    {
        $moto = Vehicle::factory()->create(['category' => 'motorcycle']);

        $this->get("/vehicles/{$moto->id}")->assertInertia(fn (Assert $page) => $page->where('fipe.vehicle_type', 'motorcycles'));
    }

    public function test_cascade_endpoints_return_options_and_validate_input(): void
    {
        Http::fake([
            self::BASE.'/cars/brands' => Http::response([['code' => '23', 'name' => 'Chevrolet']]),
            self::BASE.'/cars/brands/23/models' => Http::response([['code' => '5940', 'name' => 'Onix 1.0 LT']]),
            self::BASE.'/cars/brands/23/models/5940/years' => Http::response([['code' => '2023-1', 'name' => '2023 Gasolina']]),
        ]);
        $base = "/vehicles/{$this->vehicle->id}/fipe";

        $this->getJson("{$base}/brands?type=cars")->assertOk()->assertJson(['data' => [['code' => '23', 'name' => 'Chevrolet']]]);
        $this->getJson("{$base}/models?type=cars&brand=23")->assertOk()->assertJsonPath('data.0.code', '5940');
        $this->getJson("{$base}/years?type=cars&brand=23&model=5940")->assertOk()->assertJsonPath('data.0.name', '2023 Gasolina');

        $this->getJson("{$base}/brands?type=boats")->assertStatus(422);
        $this->getJson("{$base}/models?type=cars")->assertStatus(422);
        $this->getJson("{$base}/models?type=cars&brand=../x")->assertStatus(422);
    }

    public function test_cascade_errors_are_returned_in_portuguese_with_a_status_code(): void
    {
        Http::fake([
            self::BASE.'/cars/brands' => Http::response('erro', 503),
            self::BASE.'/cars/brands/9/models' => Http::response([], 404),
            self::BASE.'/cars/brands/23/models' => Http::response('<html>', 200),
        ]);
        $base = "/vehicles/{$this->vehicle->id}/fipe";

        $this->getJson("{$base}/brands?type=cars")->assertStatus(503)->assertJsonPath('reason', 'unavailable')->assertJsonPath('message', fn ($m) => str_contains($m, 'indisponível'));
        $this->getJson("{$base}/models?type=cars&brand=9")->assertStatus(404)->assertJsonPath('reason', 'not_found');
        $this->getJson("{$base}/models?type=cars&brand=23")->assertStatus(502)->assertJsonPath('reason', 'malformed');
    }

    public function test_consulting_persists_and_exposes_the_current_quote_and_history(): void
    {
        Http::fake([self::BASE.'/cars/brands/23/models/5940/years/2023-1' => Http::response($this->quote())]);

        $this->consult()->assertRedirect("/vehicles/{$this->vehicle->id}")->assertSessionHas('success')->assertSessionHasNoErrors();

        $this->get("/vehicles/{$this->vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->where('fipe.snapshot.value_cents', 6_845_090)
            ->where('fipe.snapshot.reference_month', '2026-03')
            ->where('fipe.snapshot.is_stale', false)
            ->where('fipe.snapshot.brand', 'Chevrolet')
            ->where('fipe.snapshot.fipe_code', '004001-0')
            ->where('fipe.selection', ['type' => 'cars', 'brand_id' => '23', 'model_id' => '5940', 'year_id' => '2023-1'])
            ->has('fipe.quotes', 1)
            ->where('fipe.quotes.0.value_cents', 6_845_090));
    }

    public function test_refresh_adds_a_history_row_using_the_stored_selection(): void
    {
        Http::fake([self::BASE.'/cars/brands/23/models/5940/years/2023-1' => Http::sequence()
            ->push($this->quote())
            ->push($this->quote(['price' => 'R$ 67.000,00', 'referenceMonth' => 'abril de 2026']))]);

        $this->consult();
        $this->post("/vehicles/{$this->vehicle->id}/fipe/refresh")->assertSessionHas('success');

        $this->get("/vehicles/{$this->vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->has('fipe.quotes', 2)
            ->where('fipe.snapshot.value_cents', 6_700_000)
            ->where('fipe.quotes.1.value_cents', 6_845_090));
    }

    public function test_failure_shows_an_error_and_keeps_the_last_value_with_stale_warning(): void
    {
        Http::fake([self::BASE.'/cars/brands/23/models/5940/years/2023-1' => Http::sequence()
            ->push($this->quote())
            ->push('erro', 503)]);

        $this->consult();
        Carbon::setTestNow('2026-05-20 10:00:00');

        $this->post("/vehicles/{$this->vehicle->id}/fipe/refresh")
            ->assertRedirect("/vehicles/{$this->vehicle->id}")
            ->assertSessionHasErrors('fipe');
        $this->assertStringContainsString('indisponível', session('errors')->first('fipe'));

        $this->assertSame(1, FipeQuote::count());
        $this->get("/vehicles/{$this->vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->where('fipe.snapshot.value_cents', 6_845_090)
            ->where('fipe.snapshot.is_stale', true)
            ->where('fipe.snapshot.consulted_at', fn ($date) => str_starts_with($date, '2026-03-10')));
    }

    public function test_mismatching_version_is_rejected_with_a_clear_message(): void
    {
        Http::fake([self::BASE.'/cars/brands/23/models/5940/years/2023-1' => Http::response($this->quote(['model' => 'Prisma 1.4 LTZ']))]);

        $this->consult()->assertSessionHasErrors('fipe');
        $this->assertStringContainsString('não corresponde a este veículo', session('errors')->first('fipe'));

        $this->assertSame(0, FipeQuote::count());
        $this->assertNull($this->vehicle->fresh()->fipe_value_cents);
    }

    public function test_refresh_without_selection_reports_an_error_instead_of_inventing_a_value(): void
    {
        Http::fake();

        $this->post("/vehicles/{$this->vehicle->id}/fipe/refresh")->assertSessionHasErrors('fipe');
        $this->assertNull($this->vehicle->fresh()->fipe_value_cents);
    }

    public function test_selection_is_validated_with_portuguese_messages(): void
    {
        $this->post("/vehicles/{$this->vehicle->id}/fipe", [])->assertSessionHasErrors([
            'type' => 'Escolha o tipo de veículo.',
            'brand_id' => 'Escolha a marca.',
            'model_id' => 'Escolha o modelo.',
            'year_id' => 'Escolha o ano/versão.',
        ]);
    }
}
