<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VehicleShowTest extends TestCase
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
            'acquisition_date' => '2024-01-15',
            'tracking_start_date' => '2024-01-15',
            'acquisition_odometer' => 20_000,
            'odometer' => 30_000,
            'purchase_price_cents' => 5_000_000,
            'sale_date' => null,
            'sale_price_cents' => null,
        ], $overrides));
    }

    private function expense(Vehicle $vehicle, string $date, int $cents, ?ExpenseCategory $category = null): void
    {
        Expense::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expense_category_id' => ($category ?? ExpenseCategory::factory()->create())->id,
            'occurred_on' => $date,
            'amount_cents' => $cents,
        ]);
    }

    public function test_financial_block_matches_a_manual_calculation(): void
    {
        $vehicle = $this->vehicle();
        $fuel = ExpenseCategory::factory()->create(['name' => 'Combustível']);
        $this->expense($vehicle, '2024-03-10', 100_000, $fuel);
        $this->expense($vehicle, '2026-05-10', 50_000, $fuel);
        $this->expense($vehicle, '2026-06-02', 30_000);

        $this->get("/vehicles/{$vehicle->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Vehicles/Show')
            ->where('financial.summary.operating_cents', 180_000)
            ->where('financial.summary.tco_cents', 5_180_000)
            ->where('financial.summary.months_owned', 29)
            ->where('financial.summary.purchase_cents', 5_000_000)
            ->where('financial.summary.cost_per_km_cents', 18)
            ->where('financial.selection', ['year' => 2026, 'month' => null])
            ->where('financial.year_totals.total_cents', 80_000)
            ->where('financial.month_totals', null)
            ->has('financial.monthly', 12)
            ->where('financial.monthly.4.total_cents', 50_000)
            ->where('financial.categories.0.name', 'Combustível')
            ->where('financial.categories.0.total_cents', 50_000));
    }

    public function test_purchase_price_is_not_an_operating_expense(): void
    {
        $vehicle = $this->vehicle(['purchase_price_cents' => 9_900_000]);

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->where('financial.summary.operating_cents', 0)
            ->where('financial.year_totals.total_cents', 0)
            ->where('financial.summary.tco_cents', 9_900_000)
            ->where('financial.categories', []));
    }

    public function test_vehicle_without_expenses_has_empty_totals_and_no_invented_values(): void
    {
        $vehicle = $this->vehicle(['acquisition_odometer' => null]);

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->where('financial.year_totals.has_data', false)
            ->where('financial.year_totals.average_cents', null)
            ->where('financial.summary.cost_per_km_cents', null)
            ->where('financial.summary.distance_km', null)
            ->where('financial.summary.sale_result_cents', null)
            ->where('financial.completeness.has_data', false));
    }

    public function test_missing_or_invalid_distance_returns_null_cost_per_km(): void
    {
        $vehicle = $this->vehicle(['acquisition_odometer' => 40_000, 'odometer' => 30_000]);
        $this->expense($vehicle, '2026-02-01', 10_000);

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->where('financial.summary.cost_per_km_cents', null)
            ->where('financial.summary.operating_cents', 10_000));
    }

    public function test_year_and_month_selection_and_invalid_values_fall_back(): void
    {
        $vehicle = $this->vehicle();
        $this->expense($vehicle, '2025-03-10', 7_000);
        $this->expense($vehicle, '2025-04-10', 3_000);

        $this->get("/vehicles/{$vehicle->id}?year=2025&month=3")->assertInertia(fn (Assert $page) => $page
            ->where('financial.selection', ['year' => 2025, 'month' => 3])
            ->where('financial.year_totals.total_cents', 10_000)
            ->where('financial.month_totals.total_cents', 7_000)
            ->where('financial.categories.0.total_cents', 7_000));

        $this->get("/vehicles/{$vehicle->id}?year=1999&month=13")->assertInertia(fn (Assert $page) => $page
            ->where('financial.selection', ['year' => 2026, 'month' => null]));
        $this->get("/vehicles/{$vehicle->id}?year=abc")->assertInertia(fn (Assert $page) => $page
            ->where('financial.selection.year', 2026));
    }

    public function test_years_before_tracking_start_are_marked_as_incomplete_history(): void
    {
        $vehicle = $this->vehicle(['acquisition_date' => '2022-05-01', 'tracking_start_date' => '2024-03-01']);
        $this->expense($vehicle, '2023-06-10', 2_000);

        $this->get("/vehicles/{$vehicle->id}?year=2023")->assertInertia(fn (Assert $page) => $page
            ->where('financial.years.0.value', 2026)
            ->where('financial.years.0.incomplete', false)
            ->where('financial.years.2.value', 2024)
            ->where('financial.years.2.incomplete', true)
            ->where('financial.years.3.label', '2023 (histórico incompleto)')
            ->where('financial.years.4.value', 2022)
            ->where('financial.completeness.is_complete', false)
            ->where('financial.completeness.tracking_start_date', '2024-03-01')
            ->where('financial.year_totals.total_cents', 2_000)
            ->where('financial.summary.is_complete', false));
    }

    public function test_sold_vehicle_has_sale_result_and_stops_at_the_sale_year(): void
    {
        $vehicle = $this->vehicle([
            'status' => VehicleStatus::Sold,
            'acquisition_date' => '2022-01-10',
            'tracking_start_date' => '2022-01-10',
            'sale_date' => '2024-01-10',
            'sale_price_cents' => 4_200_000,
            'purchase_price_cents' => 5_000_000,
        ]);
        $this->expense($vehicle, '2022-06-01', 300_000);
        $this->expense($vehicle, '2025-01-01', 999_999);

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->where('financial.years.0.value', 2024)
            ->where('financial.selection.year', 2024)
            ->where('financial.summary.sale_result_cents', -1_100_000)
            ->where('financial.summary.operating_cents', 300_000)
            ->where('financial.summary.ownership_end', '2024-01-10')
            ->where('financial.summary.months_owned', 24));
    }

    public function test_sold_vehicle_without_prices_has_no_sale_result(): void
    {
        $vehicle = $this->vehicle([
            'status' => VehicleStatus::Sold,
            'sale_date' => '2025-02-10',
            'sale_price_cents' => 3_000_000,
            'purchase_price_cents' => null,
        ]);

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->where('financial.summary.sale_result_cents', null)
            ->where('financial.summary.purchase_known', false));
    }

    public function test_header_data_for_the_page_is_present(): void
    {
        $vehicle = $this->vehicle(['plate' => 'ABC1D23', 'odometer' => 31_000]);

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->where('vehicle.formatted_plate', 'ABC1D23')
            ->where('vehicle.odometer', 31_000)
            ->where('vehicle.ownership_label', '2 anos e 5 meses')
            ->has('vehicle.photo_url')
            ->where('vehicle.status', 'active'));
    }
}
