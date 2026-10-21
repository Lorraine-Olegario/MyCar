<?php

namespace Tests\Feature;

use App\Domain\Recurring\RecurringExpenseService;
use App\Enums\VehicleStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Fueling;
use App\Models\Maintenance;
use App\Models\RecurringExpense;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    private function expense(Vehicle $vehicle, string $date, int $cents, ?ExpenseCategory $category = null): Expense
    {
        return Expense::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expense_category_id' => ($category ?? ExpenseCategory::factory()->create())->id,
            'occurred_on' => $date,
            'amount_cents' => $cents,
        ]);
    }

    public function test_dashboard_works_without_any_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('vehicle', null)
                ->where('vehicles', [])
                ->where('kpis.active_vehicles', 0)
                ->where('kpis.month_cents', 0)
                ->where('kpis.year_cents', 0)
                ->where('kpis.total_cents', 0)
                ->where('kpis.forecast_cents', 0)
                ->where('kpis.fipe_cents', null)
                ->where('indicators.month_cents', null)
                ->where('indicators.cost_per_km_cents', null)
                ->where('indicators.average_consumption', null)
                ->where('indicators.next_maintenance', null)
                ->where('charts.categories', [])
                ->where('charts.vehicles', [])
                ->has('charts.monthly', 12)
                ->where('upcoming', [])
                ->where('filters.period', 'last_12_months'));
    }

    public function test_dashboard_shows_latest_active_vehicle_and_ignores_sold_ones(): void
    {
        Vehicle::factory()->create(['status' => VehicleStatus::Sold, 'model' => 'Vendido']);
        $active = Vehicle::factory()->create(['status' => VehicleStatus::Active, 'model' => 'Onix']);

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page->where('vehicle.id', $active->id));
    }

    public function test_active_count_excludes_sold_and_archived_vehicles(): void
    {
        Vehicle::factory()->count(2)->create(['status' => VehicleStatus::Active]);
        Vehicle::factory()->sold()->create();
        Vehicle::factory()->create()->delete();

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('kpis.active_vehicles', 2)
            ->has('vehicles', 3));
    }

    public function test_month_year_and_historical_totals_come_from_occurred_on(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active, 'tracking_start_date' => '2020-01-01']);
        $this->expense($vehicle, '2026-06-01', 1_000);
        $this->expense($vehicle, '2026-06-30', 2_000);
        $this->expense($vehicle, '2026-01-10', 4_000);
        $this->expense($vehicle, '2025-12-31', 8_000);
        $this->expense($vehicle, '2020-03-10', 16_000);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('kpis.month_cents', 3_000)
            ->where('kpis.year_cents', 7_000)
            ->where('kpis.total_cents', 31_000)
            ->where('indicators.month_cents', 3_000));
    }

    public function test_sold_and_archived_vehicle_expenses_stay_out_of_the_default_totals(): void
    {
        $active = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        $sold = Vehicle::factory()->create(['status' => VehicleStatus::Sold, 'acquisition_date' => '2020-01-01', 'sale_date' => '2026-06-10', 'sale_price_cents' => 1_000_000]);
        $archived = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        $archived->delete();

        $this->expense($active, '2026-06-05', 1_000);
        $this->expense($sold, '2026-06-05', 2_000);
        $this->expense($archived, '2026-06-05', 4_000);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('kpis.month_cents', 1_000));
        $this->get("/?vehicle_id={$sold->id}")->assertInertia(fn (Assert $page) => $page->where('kpis.month_cents', 2_000));
    }

    public function test_period_filter_changes_charts_but_not_the_kpis_and_is_echoed(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active, 'tracking_start_date' => '2020-01-01']);
        $this->expense($vehicle, '2026-06-05', 1_000);
        $this->expense($vehicle, '2024-03-05', 5_000);

        $this->get('/?period=custom&from=2024-01-01&to=2024-12-31')->assertInertia(fn (Assert $page) => $page
            ->where('filters.period', 'custom')
            ->where('filters.from', '2024-01-01')
            ->where('filters.to', '2024-12-31')
            ->where('period.from', '2024-01-01')
            ->has('charts.monthly', 12)
            ->where('charts.monthly.2.total_cents', 5_000)
            ->where('charts.vehicles.0.total_cents', 5_000)
            ->where('kpis.month_cents', 1_000)
            ->where('kpis.total_cents', 6_000));

        $this->get('/?period=this_year')->assertInertia(fn (Assert $page) => $page
            ->where('period.from', '2026-01-01')
            ->where('period.to', '2026-12-31')
            ->has('charts.monthly', 12));

        $this->get('/?period=all')->assertInertia(fn (Assert $page) => $page
            ->where('period.from', null)
            ->where('charts.vehicles.0.total_cents', 6_000));
    }

    public function test_invalid_filters_fall_back_to_the_default_period(): void
    {
        $this->get('/?period=banana')->assertOk()->assertInertia(fn (Assert $page) => $page->where('filters.period', 'last_12_months'));
        $this->get('/?period=custom&from=2024-13-45&to=abc')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('filters.period', 'last_12_months')
            ->where('filters.from', ''));
        $this->get('/?period=custom&from=2024-12-31&to=2024-01-01')->assertInertia(fn (Assert $page) => $page
            ->where('filters.from', '2024-01-01')
            ->where('filters.to', '2024-12-31'));
        $this->get('/?vehicle_id=abc')->assertOk()->assertInertia(fn (Assert $page) => $page->where('filters.vehicle_id', ''));
    }

    public function test_vehicle_filter_limits_every_block(): void
    {
        $a = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        $b = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        $this->expense($a, '2026-06-05', 1_000);
        $this->expense($b, '2026-06-06', 4_000);

        $this->get("/?vehicle_id={$a->id}")->assertInertia(fn (Assert $page) => $page
            ->where('vehicle.id', $a->id)
            ->where('kpis.month_cents', 1_000)
            ->where('kpis.active_vehicles', 1)
            ->has('charts.vehicles', 1)
            ->where('filters.vehicle_id', (string) $a->id));
    }

    public function test_category_chart_matches_the_reports_service(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        $fuel = ExpenseCategory::factory()->create(['name' => 'Combustível']);
        $this->expense($vehicle, '2026-05-10', 6_000, $fuel);
        $this->expense($vehicle, '2026-05-11', 2_000);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('charts.categories.0.name', 'Combustível')
            ->where('charts.categories.0.total_cents', 6_000)
            ->where('charts.categories.0.share', 75)
            ->where('charts.monthly.10.month', '2026-05')
            ->where('charts.monthly.10.total_cents', 8_000));
    }

    public function test_forecasts_and_upcoming_dues_are_separate_from_realized_totals(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active]);
        app(RecurringExpenseService::class)->create(RecurringExpense::factory()->make([
            'vehicle_id' => $vehicle->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'description' => 'Seguro',
            'start_date' => '2026-06-01',
            'due_day' => 20,
            'expected_amount_cents' => 50_000,
        ])->getAttributes());

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('kpis.total_cents', 0)
            ->where('kpis.month_cents', 0)
            ->where('kpis.forecast_cents', 200_000)
            ->where('upcoming.0.kind', 'recurring')
            ->where('upcoming.0.description', 'Seguro')
            ->where('upcoming.0.due_date', '2026-06-20')
            ->where('upcoming.0.expected_amount_cents', 50_000));
    }

    public function test_upcoming_includes_maintenance_due_and_next_maintenance_indicator(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active, 'odometer' => 40_000]);
        $expense = $this->expense($vehicle, '2026-05-01', 30_000);
        Maintenance::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expense_id' => $expense->id,
            'next_due_on' => '2026-06-30',
            'next_due_odometer' => null,
        ]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('upcoming.0.kind', 'maintenance')
            ->where('upcoming.0.due_date', '2026-06-30')
            ->where('upcoming.0.is_overdue', false)
            ->where('upcoming.0.expected_amount_cents', null)
            ->where('indicators.next_maintenance.status', 'due_soon')
            ->where('kpis.total_cents', 30_000));
    }

    public function test_vehicle_indicators_use_null_when_data_is_insufficient(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active, 'acquisition_odometer' => null]);
        $this->expense($vehicle, '2026-06-05', 1_000);
        Fueling::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('indicators.cost_per_km_cents', null)
            ->where('indicators.average_consumption', null));
    }

    public function test_completeness_notice_data_is_sent_for_periods_before_tracking(): void
    {
        Vehicle::factory()->create(['status' => VehicleStatus::Active, 'acquisition_date' => '2022-01-01', 'tracking_start_date' => '2025-01-01']);

        $this->get('/?period=custom&from=2024-01-01&to=2024-12-31')->assertInertia(fn (Assert $page) => $page
            ->where('completeness.is_complete', false)
            ->where('completeness.incomplete_vehicles.0.tracking_start_date', '2025-01-01'));

        $this->get('/?period=this_year')->assertInertia(fn (Assert $page) => $page->where('completeness.is_complete', true));
    }

    public function test_fipe_is_never_part_of_the_spending_totals(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Active, 'purchase_price_cents' => 9_000_000]);
        $this->expense($vehicle, '2026-06-05', 1_000);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('kpis.fipe_cents', null)
            ->where('kpis.total_cents', 1_000)
            ->where('kpis.year_cents', 1_000));
    }
}
