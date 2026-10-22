<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FipeQuote;
use App\Models\RecurringExpenseOccurrence;
use App\Models\Vehicle;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Ciclo completo, usando só as rotas da aplicação: veículo antigo → despesas retroativas →
 * abastecimentos → recorrência paga → FIPE (fake) → dashboard/relatórios → venda → arquivamento.
 * Datas fixas, rede fake e banco em memória: determinístico e independente de ordem.
 */
class FullFlowTest extends TestCase
{
    use RefreshDatabase;

    private const FIPE_URL = 'https://fipe.test/api/v2/cars/brands/23/models/5940/years/2021-1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-06-15 10:00:00');
        config(['services.fipe.base_url' => 'https://fipe.test/api/v2', 'services.fipe.retries' => 1, 'services.fipe.retry_sleep_ms' => 0]);
        Cache::flush();
        Http::preventStrayRequests();
        Http::fake([self::FIPE_URL => Http::response([
            'brand' => 'Chevrolet', 'codeFipe' => '004001-0', 'fuel' => 'Flex', 'model' => 'Onix 1.0 LT 12V Flex',
            'modelYear' => 2021, 'price' => 'R$ 60.000,00', 'referenceMonth' => 'junho de 2026',
        ])]);

        $this->seed(ExpenseCategorySeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function vehiclePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Chevrolet Onix 1.0 LT', 'brand' => 'Chevrolet', 'model' => 'Onix', 'version' => '1.0 LT',
            'manufacture_year' => 2020, 'model_year' => 2021, 'category' => 'car', 'plate' => 'ABC1D23', 'color' => 'Prata',
            'fuel' => 'flex', 'odometer' => 20_000, 'acquisition_date' => '2021-03-10', 'acquisition_odometer' => 10_000,
            'purchase_price_cents' => 5_000_000, 'status' => 'active', 'tracking_start_date' => '2023-01-01',
        ], $overrides);
    }

    private function category(string $slug): int
    {
        return ExpenseCategory::where('slug', $slug)->value('id');
    }

    /**
     * Monta o cenário completo e devolve o veículo.
     */
    private function buildScenario(): Vehicle
    {
        $this->post('/vehicles', $this->vehiclePayload())->assertSessionHasNoErrors();
        $vehicle = Vehicle::firstOrFail();

        foreach ([['2022-05-10', 'combustivel', 10_000], ['2023-08-15', 'seguro', 20_000], ['2025-02-01', 'ipva', 40_000]] as [$date, $slug, $cents]) {
            $this->post('/expenses', [
                'vehicle_id' => $vehicle->id, 'expense_category_id' => $this->category($slug),
                'description' => "Despesa {$date}", 'amount_cents' => $cents, 'occurred_on' => $date,
            ])->assertSessionHasNoErrors();
        }

        $this->post('/fuelings', ['vehicle_id' => $vehicle->id, 'fueled_on' => '2026-05-01', 'odometer' => 21_000, 'liters' => '40', 'price_per_liter' => '6.000', 'fuel_type' => 'flex', 'full_tank' => true])->assertSessionHasNoErrors();
        $this->post('/fuelings', ['vehicle_id' => $vehicle->id, 'fueled_on' => '2026-05-20', 'odometer' => 21_500, 'liters' => '30', 'price_per_liter' => '6.000', 'fuel_type' => 'flex', 'full_tank' => true])->assertSessionHasNoErrors();

        $this->post('/recurring', [
            'vehicle_id' => $vehicle->id, 'expense_category_id' => $this->category('seguro'), 'description' => 'Seguro anual',
            'expected_amount_cents' => 120_000, 'frequency' => 'annual', 'start_date' => '2026-06-01', 'due_day' => 20,
        ])->assertSessionHasNoErrors();

        $occurrence = RecurringExpenseOccurrence::orderBy('competence_date')->firstOrFail();
        $this->post("/recurring/occurrences/{$occurrence->id}/pay", ['paid_on' => '2026-06-12', 'paid_amount_cents' => 118_000])->assertSessionHas('success');

        $this->post("/vehicles/{$vehicle->id}/fipe", ['type' => 'cars', 'brand_id' => '23', 'model_id' => '5940', 'year_id' => '2021-1'])->assertSessionHasNoErrors();

        return $vehicle->fresh();
    }

    public function test_full_lifecycle_numbers_match_across_dashboard_vehicle_page_and_reports(): void
    {
        $vehicle = $this->buildScenario();

        // Dados gerados pelo fluxo.
        $this->assertSame(4, Expense::count());
        $this->assertSame(21_500, $vehicle->odometer);
        $this->assertSame(6_000_000, $vehicle->fipe_value_cents);
        $this->assertSame(1, FipeQuote::count());
        $this->assertSame(1, Expense::whereNotNull('recurring_expense_id')->count());

        $operating = 10_000 + 20_000 + 40_000 + 118_000;

        // Dashboard (todo o período), página do veículo e relatórios coincidem.
        $dashboard = $this->get("/?period=all&vehicle_id={$vehicle->id}")->assertOk()->viewData('page')['props'];
        $page = $this->get("/vehicles/{$vehicle->id}?year=2026")->assertOk()->viewData('page')['props'];

        $this->assertSame($operating, $dashboard['kpis']['total_cents']);
        $this->assertSame($operating, $page['financial']['summary']['operating_cents']);
        $this->assertSame($operating, array_sum(array_column($dashboard['charts']['vehicles'], 'total_cents')));
        $this->assertSame($operating, array_sum(array_column($dashboard['charts']['categories'], 'total_cents')));
        $this->assertSame($operating, array_sum(array_column($dashboard['charts']['monthly'], 'total_cents')));
        $this->assertSame(5_000_000 + $operating, $page['financial']['summary']['tco_cents']);
        $this->assertSame(118_000, $page['financial']['year_totals']['total_cents']);

        foreach (['expense-history' => 'amount_cents', 'vehicle-comparison' => 'total_cents', 'by-category' => 'total_cents', 'yearly-by-vehicle' => 'total_cents'] as $report => $key) {
            $this->get("/reports/{$report}?period=all&vehicle_id={$vehicle->id}")->assertInertia(fn (Assert $p) => $p->where("totals.{$key}", $operating));
        }

        // Consumo (tanque cheio) e FIPE separados dos gastos.
        $this->get('/reports/fuel-history')->assertInertia(fn (Assert $p) => $p
            ->where('rows.0.km_per_liter', 16.67)
            ->where('totals.km_per_liter', 16.67)
            ->where('totals.total_cents', 42_000));
        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $p) => $p
            ->where('fipe.snapshot.value_cents', 6_000_000)
            ->where('fipe.snapshot.is_stale', false)
            ->where('financial.summary.operating_cents', $operating));
        $this->get('/')->assertInertia(fn (Assert $p) => $p->where('kpis.fipe_cents', 6_000_000)->where('kpis.total_cents', $operating));

        // Previsão (paga) aparece como pago, sem previsão em aberto no mesmo mês.
        $this->get('/reports/forecast-vs-paid?period=this_year')->assertInertia(fn (Assert $p) => $p
            ->where('totals.paid_cents', 118_000)
            ->where('totals.forecast_cents', 0));
    }

    public function test_incomplete_history_is_flagged_before_tracking_and_complete_after(): void
    {
        $vehicle = $this->buildScenario();

        $this->get("/?period=custom&from=2022-01-01&to=2022-12-31&vehicle_id={$vehicle->id}")->assertInertia(fn (Assert $p) => $p
            ->where('completeness.is_complete', false)
            ->where('completeness.incomplete_vehicles.0.tracking_start_date', '2023-01-01')
            ->where('charts.vehicles.0.total_cents', 10_000));

        $this->get("/reports/by-category?period=custom&from=2023-01-01&to=2026-12-31&vehicle_id={$vehicle->id}")->assertInertia(fn (Assert $p) => $p
            ->where('completeness.is_complete', true));

        $this->get("/vehicles/{$vehicle->id}?year=2022")->assertInertia(fn (Assert $p) => $p
            ->where('financial.completeness.is_complete', false)
            ->where('financial.years', fn ($years) => collect($years)->firstWhere('value', 2022)['incomplete'] === true));
    }

    public function test_selling_the_vehicle_moves_it_out_of_current_indicators_and_computes_the_sale_result(): void
    {
        $vehicle = $this->buildScenario();
        $operating = 188_000;

        $this->put("/vehicles/{$vehicle->id}", $this->vehiclePayload([
            'odometer' => 21_500, 'status' => 'sold', 'sale_date' => '2026-06-14', 'sale_price_cents' => 4_500_000,
        ]))->assertSessionHasNoErrors();

        // Despesa de 12/06 está dentro do período de propriedade; o resultado confere com o cálculo manual.
        $expected = 4_500_000 - 5_000_000 - $operating;

        $this->get('/reports/sale-result')->assertInertia(fn (Assert $p) => $p
            ->has('rows', 1)
            ->where('rows.0.sale_result_cents', $expected)
            ->where('rows.0.operating_cents', $operating)
            ->where('rows.0.months_owned', 63)
            ->where('totals.sale_result_cents', $expected));
        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $p) => $p
            ->where('vehicle.status', 'sold')
            ->where('financial.summary.sale_result_cents', $expected)
            ->where('financial.summary.ownership_end', '2026-06-14')
            ->where('financial.selection.year', 2026));

        // Fora dos indicadores correntes, mas consultável como histórico.
        $this->get('/')->assertInertia(fn (Assert $p) => $p
            ->where('kpis.active_vehicles', 0)
            ->where('kpis.total_cents', 0)
            ->where('kpis.fipe_cents', null)
            ->where('fipe.vehicles', 0));
        $this->get("/?period=all&vehicle_id={$vehicle->id}")->assertInertia(fn (Assert $p) => $p->where('kpis.total_cents', $operating));
        $this->get('/vehicles?view=sold')->assertInertia(fn (Assert $p) => $p->has('vehicles.data', 1)->where('counts.sold', 1));
        $this->get('/reports/ownership-cost')->assertInertia(fn (Assert $p) => $p->where('rows.0.status', 'Vendido')->where('totals.operating_cents', $operating));
    }

    public function test_archiving_keeps_all_history_and_restoring_brings_the_vehicle_back(): void
    {
        $vehicle = $this->buildScenario();

        $this->delete("/vehicles/{$vehicle->id}")->assertRedirect('/vehicles');

        $this->get("/vehicles/{$vehicle->id}")->assertNotFound();
        $this->get('/vehicles')->assertInertia(fn (Assert $p) => $p->has('vehicles.data', 0)->where('counts.archived', 1));
        $this->get('/vehicles?view=archived')->assertInertia(fn (Assert $p) => $p->has('vehicles.data', 1));
        $this->get('/')->assertInertia(fn (Assert $p) => $p->where('kpis.total_cents', 0)->where('kpis.active_vehicles', 0));
        $this->get('/reports/by-category?period=all')->assertInertia(fn (Assert $p) => $p->where('rows', []));
        $this->get('/reports/by-category?period=all&include_archived=1')->assertInertia(fn (Assert $p) => $p->where('totals.total_cents', 188_000));

        $this->assertSame(4, Expense::count());
        $this->assertSame(1, FipeQuote::count());

        $this->patch("/vehicles/{$vehicle->id}/restore")->assertSessionHasNoErrors();
        $this->get('/')->assertInertia(fn (Assert $p) => $p->where('kpis.total_cents', 188_000)->where('kpis.fipe_cents', 6_000_000));
    }

    public function test_application_without_any_data_renders_every_main_screen(): void
    {
        foreach (['/', '/vehicles', '/expenses', '/recurring', '/fuelings', '/maintenances', '/categories', '/reports'] as $uri) {
            $this->get($uri)->assertOk();
        }

        foreach (['monthly-by-vehicle', 'yearly-by-vehicle', 'expense-history', 'vehicle-comparison', 'by-category', 'fuel-history', 'forecast-vs-paid', 'fipe-evolution', 'ownership-cost', 'sale-result'] as $report) {
            // Relatórios de matriz mostram os meses/anos do período zerados; os demais ficam sem linhas.
            $matrix = in_array($report, ['monthly-by-vehicle', 'yearly-by-vehicle'], true);

            $this->get("/reports/{$report}")->assertOk()->assertInertia(fn (Assert $p) => $matrix
                ? $p->where('rows', fn ($rows) => array_sum(array_column($rows->toArray(), 'total_cents')) === 0)
                : $p->where('rows', []));
            $this->get("/reports/{$report}/csv")->assertOk();
        }
    }

    public function test_expense_without_vehicle_data_in_the_period_has_no_invented_values(): void
    {
        $this->post('/vehicles', $this->vehiclePayload(['acquisition_odometer' => null, 'purchase_price_cents' => null]))->assertSessionHasNoErrors();
        $vehicle = Vehicle::firstOrFail();

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $p) => $p
            ->where('financial.summary.cost_per_km_cents', null)
            ->where('financial.summary.purchase_known', false)
            ->where('financial.year_totals.has_data', false)
            ->where('fipe.snapshot', null));
    }
}
