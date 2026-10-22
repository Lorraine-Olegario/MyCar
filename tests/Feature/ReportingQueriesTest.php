<?php

namespace Tests\Feature;

use App\Domain\Recurring\RecurringExpenseService;
use App\Domain\Reporting\FinancialQueries;
use App\Domain\Reporting\Period;
use App\Domain\Reporting\ReportFilters;
use App\Enums\RecurrenceFrequency;
use App\Enums\VehicleStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RecurringExpense;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportingQueriesTest extends TestCase
{
    use RefreshDatabase;

    private FinancialQueries $queries;

    private ExpenseCategory $fuel;

    private ExpenseCategory $insurance;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-15');
        $this->queries = app(FinancialQueries::class);
        $this->fuel = ExpenseCategory::factory()->create(['name' => 'Combustível']);
        $this->insurance = ExpenseCategory::factory()->create(['name' => 'Seguro']);
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

    private function expense(Vehicle $vehicle, string $date, int $cents, ?ExpenseCategory $category = null): Expense
    {
        return Expense::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expense_category_id' => ($category ?? $this->fuel)->id,
            'occurred_on' => $date,
            'amount_cents' => $cents,
        ]);
    }

    private function filters(?Period $period = null, ?int $vehicleId = null, ?int $categoryId = null): ReportFilters
    {
        return new ReportFilters($period ?? Period::all(), $vehicleId, $categoryId);
    }

    public function test_period_limits_are_inclusive_including_leap_day_and_year_ends(): void
    {
        $vehicle = $this->vehicle();
        $this->expense($vehicle, '2023-12-31', 100);
        $this->expense($vehicle, '2024-01-01', 200);
        $this->expense($vehicle, '2024-02-29', 400);
        $this->expense($vehicle, '2024-12-31', 800);
        $this->expense($vehicle, '2025-01-01', 1_600);

        $this->assertSame(1_400, $this->queries->totals($this->filters(Period::ofYear(2024)))['total_cents']);
        $this->assertSame(400, $this->queries->totals($this->filters(Period::ofMonth(2024, 2)))['total_cents']);
        $this->assertSame(400, $this->queries->totals($this->filters(Period::between('2024-02-29', '2024-02-29')))['total_cents']);
        $this->assertSame(3_100, $this->queries->totals($this->filters())['total_cents']);
    }

    public function test_totals_use_occurred_on_and_retroactive_expenses_count(): void
    {
        $vehicle = $this->vehicle();
        $expense = $this->expense($vehicle, '2021-05-10', 5_000);

        $this->assertSame(2026, $expense->created_at->year);
        $this->assertSame(5_000, $this->queries->totals($this->filters(Period::ofYear(2021)))['total_cents']);
        $this->assertSame(0, $this->queries->totals($this->filters(Period::ofYear(2026)))['total_cents']);
    }

    public function test_period_without_data_returns_zero_with_flags_not_averages(): void
    {
        $this->vehicle();

        $totals = $this->queries->totals($this->filters(Period::ofYear(2024)));

        $this->assertSame(0, $totals['total_cents']);
        $this->assertSame(0, $totals['count']);
        $this->assertNull($totals['average_cents']);
        $this->assertFalse($totals['has_data']);
        $this->assertFalse($this->queries->completeness($this->filters(Period::ofYear(2024)))['has_data']);
    }

    public function test_monthly_series_fills_empty_months_and_yearly_series_fills_empty_years(): void
    {
        $vehicle = $this->vehicle();
        $this->expense($vehicle, '2024-02-10', 300);
        $this->expense($vehicle, '2024-02-20', 200);
        $this->expense($vehicle, '2026-01-05', 700);

        $monthly = $this->queries->monthlySeries($this->filters(Period::ofYear(2024)));
        $this->assertCount(12, $monthly);
        $this->assertSame(['month' => '2024-02', 'total_cents' => 500, 'count' => 2], $monthly[1]);
        $this->assertSame(0, $monthly[0]['total_cents']);

        $yearly = $this->queries->yearlySeries($this->filters(Period::between('2024-01-01', '2026-12-31')));
        $this->assertSame([500, 0, 700], array_column($yearly, 'total_cents'));
        $this->assertSame(['2024', '2025', '2026'], array_column($yearly, 'month'));

        $all = $this->queries->monthlySeries($this->filters());
        $this->assertSame('2024-02', $all[0]['month']);
        $this->assertSame('2026-01', end($all)['month']);
        $this->assertCount(24, $all);
    }

    public function test_last_twelve_months_period_is_calendar_aligned(): void
    {
        $period = Period::lastMonths(12);

        $this->assertSame('2025-07-01', $period->from->toDateString());
        $this->assertSame('2026-06-30', $period->to->toDateString());
        $this->assertCount(12, $period->months());
        $this->assertTrue($period->contains('2026-06-30'));
        $this->assertFalse($period->contains('2026-07-01'));
    }

    public function test_default_scope_excludes_sold_and_archived_vehicles(): void
    {
        $active = $this->vehicle();
        $sold = $this->vehicle(['status' => VehicleStatus::Sold, 'sale_date' => '2025-12-31', 'sale_price_cents' => 3_000_000]);
        $archived = $this->vehicle();
        $archived->delete();

        $this->expense($active, '2025-03-10', 1_000);
        $this->expense($sold, '2025-03-10', 2_000);
        $this->expense($archived, '2025-03-10', 4_000);

        $this->assertSame(1_000, $this->queries->totals($this->filters())['total_cents']);
        $this->assertSame(3_000, $this->queries->totals(new ReportFilters(Period::all(), includeSold: true))['total_cents']);
        $this->assertSame(5_000, $this->queries->totals(new ReportFilters(Period::all(), includeArchived: true))['total_cents']);
        $this->assertSame(2_000, $this->queries->totals($this->filters(null, $sold->id))['total_cents']);
        $this->assertSame(4_000, $this->queries->totals($this->filters(null, $archived->id))['total_cents']);
    }

    public function test_sold_vehicle_is_limited_to_acquisition_to_sale_period(): void
    {
        $sold = $this->vehicle(['status' => VehicleStatus::Sold, 'acquisition_date' => '2022-01-10', 'sale_date' => '2024-06-30', 'sale_price_cents' => 3_000_000]);

        $this->expense($sold, '2021-12-31', 1_000);
        $this->expense($sold, '2022-01-10', 2_000);
        $this->expense($sold, '2024-06-30', 4_000);
        $this->expense($sold, '2024-07-01', 8_000);

        $this->assertSame(6_000, $this->queries->totals($this->filters(null, $sold->id))['total_cents']);
    }

    public function test_filters_by_vehicle_and_category_combine(): void
    {
        $a = $this->vehicle();
        $b = $this->vehicle();
        $this->expense($a, '2025-01-10', 100, $this->fuel);
        $this->expense($a, '2025-01-11', 200, $this->insurance);
        $this->expense($b, '2025-01-12', 400, $this->fuel);

        $this->assertSame(700, $this->queries->totals($this->filters())['total_cents']);
        $this->assertSame(300, $this->queries->totals($this->filters(null, $a->id))['total_cents']);
        $this->assertSame(500, $this->queries->totals($this->filters(null, null, $this->fuel->id))['total_cents']);
        $this->assertSame(100, $this->queries->totals($this->filters(null, $a->id, $this->fuel->id))['total_cents']);
    }

    public function test_by_category_and_by_vehicle_are_ordered_with_shares(): void
    {
        $a = $this->vehicle(['name' => 'Alfa']);
        $b = $this->vehicle(['name' => 'Beta']);
        $this->expense($a, '2025-01-10', 600, $this->fuel);
        $this->expense($a, '2025-01-11', 200, $this->insurance);
        $this->expense($b, '2025-01-12', 200, $this->fuel);

        $categories = $this->queries->byCategory($this->filters());
        $this->assertSame(['Combustível', 'Seguro'], array_column($categories, 'name'));
        $this->assertSame([800, 200], array_column($categories, 'total_cents'));
        $this->assertSame([80.0, 20.0], array_column($categories, 'share'));

        $vehicles = $this->queries->byVehicle($this->filters());
        $this->assertSame(['Alfa', 'Beta'], array_column($vehicles, 'name'));
        $this->assertSame([800, 200], array_column($vehicles, 'total_cents'));
        $this->assertSame([2, 1], array_column($vehicles, 'count'));
    }

    public function test_forecast_never_contaminates_realized_totals_and_paid_is_separate(): void
    {
        $vehicle = $this->vehicle();
        $service = app(RecurringExpenseService::class);

        $recurring = $service->create(RecurringExpense::factory()->frequency(RecurrenceFrequency::Monthly)->make([
            'vehicle_id' => $vehicle->id,
            'expense_category_id' => $this->insurance->id,
            'start_date' => '2026-06-01',
            'due_day' => 20,
            'expected_amount_cents' => 10_000,
        ])->getAttributes());

        $period = Period::between('2026-06-01', '2026-08-31');
        $this->assertSame(0, $this->queries->totals($this->filters($period))['total_cents']);

        $before = $this->queries->forecastVsPaid($this->filters($period));
        $this->assertSame(0, $before['paid_cents']);
        $this->assertSame(30_000, $before['forecast_cents']);

        $service->confirmPayment($recurring->occurrences()->orderBy('competence_date')->first(), '2026-06-20', 9_500);

        $after = $this->queries->forecastVsPaid($this->filters($period));
        $this->assertSame(9_500, $after['paid_cents']);
        $this->assertSame(20_000, $after['forecast_cents']);
        $this->assertSame(9_500, $this->queries->totals($this->filters($period))['total_cents']);
        $this->assertSame(['2026-06', '2026-07', '2026-08'], array_column($after['months'], 'month'));
        $this->assertSame([9_500, 0, 0], array_column($after['months'], 'paid_cents'));
        $this->assertSame([0, 10_000, 10_000], array_column($after['months'], 'forecast_cents'));
    }

    public function test_upcoming_due_lists_open_forecasts_with_overdue_flag(): void
    {
        $vehicle = $this->vehicle();
        app(RecurringExpenseService::class)->create(RecurringExpense::factory()->make([
            'vehicle_id' => $vehicle->id,
            'expense_category_id' => $this->insurance->id,
            'start_date' => '2026-06-01',
            'due_day' => 10,
        ])->getAttributes());

        $items = $this->queries->upcomingDue($this->filters(), withinDays: 30);

        $this->assertSame(['2026-06-10', '2026-07-10'], array_column($items, 'due_date'));
        $this->assertTrue($items[0]['is_overdue']);
        $this->assertFalse($items[1]['is_overdue']);
        $this->assertSame([], $this->queries->upcomingDue($this->filters(null, $this->vehicle()->id)));
    }

    public function test_vehicle_summary_matches_a_manual_calculation(): void
    {
        $vehicle = $this->vehicle([
            'acquisition_date' => '2024-01-15',
            'tracking_start_date' => '2024-01-15',
            'acquisition_odometer' => 20_000,
            'odometer' => 30_000,
            'purchase_price_cents' => 5_000_000,
        ]);
        $this->expense($vehicle, '2024-03-10', 100_000);
        $this->expense($vehicle, '2025-05-10', 150_000);

        $summary = $this->queries->vehicleSummary($vehicle, Carbon::parse('2026-01-15'));

        $this->assertSame(250_000, $summary['operating_cents']);
        $this->assertSame(5_250_000, $summary['tco_cents']);
        $this->assertSame(24, $summary['months_owned']);
        $this->assertSame(218_750, $summary['average_monthly_cost_cents']);
        $this->assertSame(10_417, $summary['average_monthly_operating_cents']);
        $this->assertSame(10_000, $summary['distance_km']);
        $this->assertSame(25.0, $summary['cost_per_km_cents']);
        $this->assertNull($summary['sale_result_cents']);
        $this->assertTrue($summary['is_complete']);
        $this->assertTrue($summary['purchase_known']);
    }

    public function test_vehicle_summary_returns_null_instead_of_zero_without_valid_distance(): void
    {
        $noStart = $this->vehicle(['acquisition_odometer' => null, 'odometer' => 30_000]);
        $backwards = $this->vehicle(['acquisition_odometer' => 40_000, 'odometer' => 30_000]);

        foreach ([$noStart, $backwards] as $vehicle) {
            $this->expense($vehicle, '2025-01-10', 1_000);
            $summary = $this->queries->vehicleSummary($vehicle);

            $this->assertNull($summary['cost_per_km_cents']);
            $this->assertNull($summary['distance_km']);
        }
    }

    public function test_vehicle_without_expenses_has_zero_operating_cost_and_minimum_one_month(): void
    {
        $vehicle = $this->vehicle(['acquisition_date' => '2026-06-10', 'purchase_price_cents' => 3_000_000, 'purchase_price_cents' => 3_000_000]);

        $summary = $this->queries->vehicleSummary($vehicle);

        $this->assertSame(0, $summary['operating_cents']);
        $this->assertSame(3_000_000, $summary['tco_cents']);
        $this->assertSame(1, $summary['months_owned']);
        $this->assertSame(3_000_000, $summary['average_monthly_cost_cents']);
        $this->assertSame(0, $summary['average_monthly_operating_cents']);
    }

    public function test_sold_vehicle_summary_has_sale_result_and_ownership_window(): void
    {
        $vehicle = $this->vehicle([
            'status' => VehicleStatus::Sold,
            'acquisition_date' => '2022-01-10',
            'sale_date' => '2024-01-10',
            'purchase_price_cents' => 5_000_000,
            'sale_price_cents' => 4_200_000,
            'acquisition_odometer' => 10_000,
            'odometer' => 50_000,
        ]);
        $this->expense($vehicle, '2022-06-01', 300_000);
        $this->expense($vehicle, '2025-01-01', 999_999);

        $summary = $this->queries->vehicleSummary($vehicle);

        $this->assertSame(300_000, $summary['operating_cents']);
        $this->assertSame(-1_100_000, $summary['sale_result_cents']);
        $this->assertSame(24, $summary['months_owned']);
        $this->assertSame('2024-01-10', $summary['ownership_end']);
        $this->assertSame(7.5, $summary['cost_per_km_cents']);
    }

    public function test_sale_result_is_null_when_purchase_price_is_unknown(): void
    {
        $vehicle = $this->vehicle([
            'status' => VehicleStatus::Sold,
            'sale_date' => '2025-01-10',
            'purchase_price_cents' => null,
            'sale_price_cents' => 4_000_000,
        ]);

        $summary = $this->queries->vehicleSummary($vehicle);

        $this->assertNull($summary['sale_result_cents']);
        $this->assertFalse($summary['purchase_known']);
    }

    public function test_completeness_flags_periods_before_tracking_start(): void
    {
        $vehicle = $this->vehicle(['name' => 'Onix', 'acquisition_date' => '2022-01-10', 'tracking_start_date' => '2024-03-01']);

        $before = $this->queries->completeness($this->filters(Period::ofYear(2023)));
        $this->assertFalse($before['is_complete']);
        $this->assertSame('2024-03-01', $before['tracking_start_date']);
        $this->assertSame($vehicle->id, $before['incomplete_vehicles'][0]['id']);

        $this->assertTrue($this->queries->completeness($this->filters(Period::between('2024-03-01', '2025-12-31')))['is_complete']);
        $this->assertFalse($this->queries->completeness($this->filters())['is_complete']);
        $this->assertFalse($this->queries->vehicleSummary($vehicle)['is_complete']);
    }

    public function test_vehicle_without_tracking_start_is_always_incomplete(): void
    {
        $this->vehicle(['tracking_start_date' => null]);

        $this->assertFalse($this->queries->completeness($this->filters(Period::ofYear(2025)))['is_complete']);
    }
}
