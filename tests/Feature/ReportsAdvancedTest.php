<?php

namespace Tests\Feature;

use App\Domain\Recurring\RecurringExpenseService;
use App\Domain\Reporting\FinancialQueries;
use App\Domain\Reporting\Period;
use App\Domain\Reporting\ReportFilters;
use App\Enums\VehicleStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Fueling;
use App\Models\RecurringExpense;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportsAdvancedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-06-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function vehicle(array $overrides = []): Vehicle
    {
        return Vehicle::factory()->create(array_merge([
            'status' => VehicleStatus::Active,
            'acquisition_date' => '2020-01-10',
            'tracking_start_date' => '2020-01-10',
            'sale_date' => null,
            'sale_price_cents' => null,
        ], $overrides));
    }

    private function fueling(Vehicle $vehicle, string $date, int $odometer, float $liters, int $cents, bool $full = true): Fueling
    {
        return Fueling::factory()->create([
            'vehicle_id' => $vehicle->id, 'fueled_on' => $date, 'odometer' => $odometer, 'liters' => $liters,
            'total_cents' => $cents, 'price_per_liter' => round($cents / 100 / $liters, 3), 'full_tank' => $full,
        ]);
    }

    private function expense(Vehicle $vehicle, string $date, int $cents): Expense
    {
        return Expense::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'occurred_on' => $date,
            'amount_cents' => $cents,
        ]);
    }

    // ------------------------------------------------------------ relatório 6

    public function test_fuel_report_follows_the_full_tank_rule_and_reports_missing_data_as_null(): void
    {
        $vehicle = $this->vehicle(['name' => 'Onix']);
        $this->fueling($vehicle, '2026-01-01', 10_000, 40, 24_000);
        $this->fueling($vehicle, '2026-01-10', 10_200, 15, 9_000, false);
        $this->fueling($vehicle, '2026-01-20', 10_500, 20, 12_000);

        $this->get('/reports/fuel-history')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('report.key', 'fuel-history')
            ->where('report.supports.category', false)
            ->where('total_rows', 3)
            ->where('rows.0.fueled_on', '2026-01-20')
            ->where('rows.0.tank', 'Cheio')
            ->where('rows.0.distance_km', 500)
            ->where('rows.0.km_per_liter', 14.29)
            ->where('rows.0.cost_per_km_cents', 42)
            ->where('rows.1.tank', 'Parcial')
            ->where('rows.1.km_per_liter', null)
            ->where('rows.2.km_per_liter', null)
            ->where('rows.2.distance_km', null)
            ->where('totals.liters', 75)
            ->where('totals.total_cents', 45_000)
            ->where('totals.km_per_liter', 14.29));
    }

    public function test_fuel_report_consumption_does_not_change_with_the_period(): void
    {
        $vehicle = $this->vehicle();
        $this->fueling($vehicle, '2025-12-20', 9_600, 40, 24_000);
        $this->fueling($vehicle, '2026-01-10', 10_000, 32, 19_200);

        $this->get('/reports/fuel-history?period=custom&from=2026-01-01&to=2026-12-31')->assertInertia(fn (Assert $page) => $page
            ->where('total_rows', 1)
            ->where('rows.0.km_per_liter', 12.5)
            ->where('totals.km_per_liter', 12.5));
    }

    public function test_fuel_report_without_data_has_null_averages(): void
    {
        $this->vehicle();

        $this->get('/reports/fuel-history')->assertInertia(fn (Assert $page) => $page
            ->where('rows', [])
            ->where('totals.km_per_liter', null)
            ->where('totals.price_per_liter', null)
            ->where('chart', null));
    }

    public function test_fuel_report_is_consistent_with_the_fueling_page(): void
    {
        $vehicle = $this->vehicle();
        $this->fueling($vehicle, '2026-01-01', 10_000, 40, 24_000);
        $this->fueling($vehicle, '2026-01-20', 10_400, 32, 19_200);

        $page = $this->get('/fuelings')->viewData('page')['props'];

        $this->get('/reports/fuel-history')->assertInertia(fn (Assert $report) => $report
            ->where('totals.km_per_liter', $page['summary']['average_km_per_liter'])
            ->where('totals.total_cents', $page['summary']['total_cents'])
            ->where('totals.liters', fn ($liters) => (float) $liters === (float) $page['summary']['total_liters']));
    }

    // ------------------------------------------------------------ relatório 7

    public function test_forecast_and_paid_are_in_separate_columns_and_totals(): void
    {
        $vehicle = $this->vehicle(['name' => 'Onix']);
        $service = app(RecurringExpenseService::class);
        $recurring = $service->create(RecurringExpense::factory()->make([
            'vehicle_id' => $vehicle->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'description' => 'Seguro',
            'frequency' => 'monthly',
            'start_date' => '2026-06-01',
            'due_day' => 20,
            'expected_amount_cents' => 10_000,
        ])->getAttributes());

        $service->confirmPayment($recurring->occurrences()->orderBy('competence_date')->first(), '2026-06-18', 9_500);
        $service->cancel($recurring->occurrences()->orderBy('competence_date')->skip(1)->first());

        $this->get('/reports/forecast-vs-paid?period=custom&from=2026-06-01&to=2026-09-30')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('columns', fn ($columns) => collect($columns)->pluck('key')->contains('forecast_cents') && collect($columns)->pluck('key')->contains('paid_cents'))
            ->where('total_rows', 3)
            ->where('rows.0.status', 'Pago')
            ->where('rows.0.paid_cents', 9_500)
            ->where('rows.0.forecast_cents', null)
            ->where('rows.1.status', 'Previsto')
            ->where('rows.1.forecast_cents', 10_000)
            ->where('rows.1.paid_cents', null)
            ->where('totals.paid_cents', 9_500)
            ->where('totals.forecast_cents', 20_000)
            ->where('chart.series.0.label', 'Previsto (em aberto)')
            ->where('chart.series.1.label', 'Pago'));
    }

    public function test_forecasts_never_contaminate_the_realized_totals_of_other_reports(): void
    {
        $vehicle = $this->vehicle();
        app(RecurringExpenseService::class)->create(RecurringExpense::factory()->make([
            'vehicle_id' => $vehicle->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'start_date' => '2026-06-01',
            'due_day' => 20,
            'expected_amount_cents' => 50_000,
        ])->getAttributes());

        $this->get('/reports/by-category?period=this_year')->assertInertia(fn (Assert $page) => $page->where('totals.total_cents', 0));
        $this->get('/reports/forecast-vs-paid?period=this_year')->assertInertia(fn (Assert $page) => $page
            ->where('totals.paid_cents', 0)
            ->where('totals.forecast_cents', 350_000));
    }

    public function test_forecast_report_matches_the_queries_service(): void
    {
        $vehicle = $this->vehicle();
        $service = app(RecurringExpenseService::class);
        $recurring = $service->create(RecurringExpense::factory()->make([
            'vehicle_id' => $vehicle->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'start_date' => '2026-06-01',
            'due_day' => 5,
            'expected_amount_cents' => 10_000,
        ])->getAttributes());
        $service->confirmPayment($recurring->occurrences()->first(), '2026-06-05', 9_000);

        $expected = app(FinancialQueries::class)->forecastVsPaid(new ReportFilters(Period::ofYear(2026)));

        $this->get('/reports/forecast-vs-paid?period=this_year')->assertInertia(fn (Assert $page) => $page
            ->where('totals.paid_cents', $expected['paid_cents'])
            ->where('totals.forecast_cents', $expected['forecast_cents']));
    }

    // ------------------------------------------------------------ relatório 9

    public function test_ownership_cost_report_matches_a_manual_calculation(): void
    {
        $vehicle = $this->vehicle([
            'name' => 'Onix',
            'acquisition_date' => '2024-01-15',
            'tracking_start_date' => '2024-01-15',
            'acquisition_odometer' => 20_000,
            'odometer' => 30_000,
            'purchase_price_cents' => 5_000_000,
        ]);
        $this->expense($vehicle, '2024-03-10', 100_000);
        $this->expense($vehicle, '2025-05-10', 150_000);

        $this->get('/reports/ownership-cost')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('report.supports', ['category' => false, 'period' => false, 'scope' => false])
            ->where('rows.0.vehicle', 'Onix')
            ->where('rows.0.purchase_cents', 5_000_000)
            ->where('rows.0.operating_cents', 250_000)
            ->where('rows.0.tco_cents', 5_250_000)
            ->where('rows.0.months_owned', 29)
            ->where('rows.0.average_monthly_cost_cents', 181_034)
            ->where('rows.0.distance_km', 10_000)
            ->where('rows.0.cost_per_km_cents', 25)
            ->where('totals.tco_cents', 5_250_000)
            ->where('completeness', null));
    }

    public function test_ownership_cost_includes_sold_vehicles_but_keeps_purchase_separate_from_operating(): void
    {
        $active = $this->vehicle(['name' => 'Ativo', 'purchase_price_cents' => 3_000_000]);
        $sold = $this->vehicle(['name' => 'Vendido', 'status' => VehicleStatus::Sold, 'sale_date' => '2025-12-31', 'sale_price_cents' => 2_000_000, 'purchase_price_cents' => 4_000_000]);
        $this->expense($active, '2026-01-10', 10_000);
        $this->expense($sold, '2025-06-10', 20_000);

        $this->get('/reports/ownership-cost')->assertInertia(fn (Assert $page) => $page
            ->has('rows', 2)
            ->where('rows.0.vehicle', 'Ativo')
            ->where('rows.0.operating_cents', 10_000)
            ->where('rows.1.vehicle', 'Vendido')
            ->where('rows.1.status', 'Vendido')
            ->where('rows.1.operating_cents', 20_000)
            ->where('totals.purchase_cents', 7_000_000)
            ->where('totals.operating_cents', 30_000));
    }

    public function test_ownership_cost_has_null_cost_per_km_without_distance(): void
    {
        $this->vehicle(['acquisition_odometer' => null, 'purchase_price_cents' => null]);

        $this->get('/reports/ownership-cost')->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.cost_per_km_cents', null)
            ->where('rows.0.distance_km', null)
            ->where('rows.0.purchase_cents', null));
    }

    // ----------------------------------------------------------- relatório 10

    public function test_sale_result_report_only_lists_sold_vehicles_with_the_service_calculation(): void
    {
        $this->vehicle(['name' => 'Ativo']);
        $sold = $this->vehicle([
            'name' => 'Vendido',
            'status' => VehicleStatus::Sold,
            'acquisition_date' => '2022-01-10',
            'sale_date' => '2024-01-10',
            'purchase_price_cents' => 5_000_000,
            'sale_price_cents' => 4_200_000,
        ]);
        $this->expense($sold, '2022-06-01', 300_000);
        $this->expense($sold, '2025-01-01', 999_999);

        $expected = app(FinancialQueries::class)->vehicleSummary($sold)['sale_result_cents'];

        $this->get('/reports/sale-result')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('rows', 1)
            ->where('rows.0.vehicle', 'Vendido')
            ->where('rows.0.purchase_cents', 5_000_000)
            ->where('rows.0.operating_cents', 300_000)
            ->where('rows.0.sale_price_cents', 4_200_000)
            ->where('rows.0.sale_result_cents', -1_100_000)
            ->where('rows.0.sale_result_cents', $expected)
            ->where('rows.0.months_owned', 24)
            ->where('totals.sale_result_cents', -1_100_000)
            ->where('chart.series.0.values', [-1_100_000]));
    }

    public function test_sale_result_does_not_include_fipe_and_is_null_without_prices(): void
    {
        $this->vehicle(['name' => 'Sem compra', 'status' => VehicleStatus::Sold, 'sale_date' => '2025-02-10', 'sale_price_cents' => 3_000_000, 'purchase_price_cents' => null]);

        $this->get('/reports/sale-result')->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.sale_result_cents', null)
            ->where('totals.sale_result_cents', null)
            ->where('chart', null)
            ->where('columns', fn ($columns) => ! collect($columns)->pluck('key')->contains(fn ($key) => str_contains($key, 'fipe'))));
    }

    public function test_sale_result_can_be_limited_to_one_vehicle(): void
    {
        $a = $this->vehicle(['name' => 'A', 'status' => VehicleStatus::Sold, 'sale_date' => '2025-02-10', 'sale_price_cents' => 1, 'purchase_price_cents' => 1]);
        $this->vehicle(['name' => 'B', 'status' => VehicleStatus::Sold, 'sale_date' => '2025-03-10', 'sale_price_cents' => 1, 'purchase_price_cents' => 1]);

        $this->get("/reports/sale-result?vehicle_id={$a->id}")->assertInertia(fn (Assert $page) => $page
            ->has('rows', 1)
            ->where('rows.0.vehicle', 'A'));
    }
}
