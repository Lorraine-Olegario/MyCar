<?php

namespace Tests\Feature;

use App\Domain\Reporting\Period;
use App\Domain\Reporting\ReportFilters;
use App\Domain\Reporting\ReportRegistry;
use App\Enums\VehicleStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private Vehicle $onix;

    private Vehicle $corolla;

    private ExpenseCategory $fuel;

    private ExpenseCategory $insurance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-06-15');

        $this->onix = $this->vehicle('Onix');
        $this->corolla = $this->vehicle('Corolla');
        $this->fuel = ExpenseCategory::factory()->create(['name' => 'Combustível']);
        $this->insurance = ExpenseCategory::factory()->create(['name' => 'Seguro']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function vehicle(string $name, array $overrides = []): Vehicle
    {
        return Vehicle::factory()->create(array_merge([
            'name' => $name,
            'status' => VehicleStatus::Active,
            'acquisition_date' => '2020-01-10',
            'tracking_start_date' => '2020-01-10',
            'sale_date' => null,
            'sale_price_cents' => null,
        ], $overrides));
    }

    private function expense(Vehicle $vehicle, string $date, int $cents, ?ExpenseCategory $category = null, array $overrides = []): Expense
    {
        return Expense::factory()->create(array_merge([
            'vehicle_id' => $vehicle->id,
            'expense_category_id' => ($category ?? $this->fuel)->id,
            'occurred_on' => $date,
            'amount_cents' => $cents,
        ], $overrides));
    }

    private function seedData(): void
    {
        $this->expense($this->onix, '2024-03-10', 1_000, $this->fuel);
        $this->expense($this->onix, '2025-01-05', 2_000, $this->insurance);
        $this->expense($this->onix, '2026-05-20', 4_000, $this->fuel);
        $this->expense($this->corolla, '2025-01-15', 8_000, $this->fuel);
        $this->expense($this->corolla, '2026-05-02', 16_000, $this->insurance);
    }

    public function test_index_lists_the_available_reports(): void
    {
        $this->get('/reports')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->has('reports', 5)
            ->where('reports.0.key', 'monthly-by-vehicle')
            ->where('reports.4.key', 'by-category'));
    }

    public function test_unknown_report_is_a_404(): void
    {
        $this->get('/reports/does-not-exist')->assertNotFound();
    }

    public function test_monthly_by_vehicle_has_one_column_per_vehicle_and_zero_filled_months(): void
    {
        $this->seedData();

        $this->get('/reports/monthly-by-vehicle?period=custom&from=2025-01-01&to=2025-03-31')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Show')
            ->where('report.key', 'monthly-by-vehicle')
            ->where('columns.0.type', 'month')
            ->where('columns', fn ($columns) => collect($columns)->pluck('label')->all() === ['Mês', 'Corolla', 'Onix', 'Total'])
            ->has('rows', 3)
            ->where('rows.0.period', '2025-01')
            ->where('rows.0.total_cents', 10_000)
            ->where('rows.1.total_cents', 0)
            ->where('totals.total_cents', 10_000)
            ->where('chart.type', 'bar')
            ->where('chart.series.0.label', 'Corolla')
            ->where('chart.series.0.values', [8_000, 0, 0]));
    }

    public function test_yearly_by_vehicle_aggregates_years_and_defaults_to_all_history(): void
    {
        $this->seedData();

        $this->get('/reports/yearly-by-vehicle')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('filters.period', 'all')
            ->where('columns.0.type', 'year')
            ->has('rows', 3)
            ->where('rows.0.period', '2024')
            ->where('rows.0.total_cents', 1_000)
            ->where('rows.1.total_cents', 10_000)
            ->where('rows.2.total_cents', 20_000)
            ->where('totals.total_cents', 31_000));
    }

    public function test_expense_history_lists_every_expense_with_retroactive_dates_and_paginates(): void
    {
        foreach (range(1, 23) as $i) {
            $this->expense($this->onix, '2021-01-'.str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT), 100);
        }
        $this->expense($this->onix, '2019-12-31', 5_000);

        $this->get('/reports/expense-history')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('total_rows', 24)
            ->has('rows', 20)
            ->where('pagination.total', 24)
            ->where('pagination.from', 1)
            ->where('pagination.to', 20)
            ->where('totals.amount_cents', 7_300));

        $this->get('/reports/expense-history?page=2')->assertInertia(fn (Assert $page) => $page
            ->has('rows', 4)
            ->where('rows.3.occurred_on', '2019-12-31'));
    }

    public function test_vehicle_comparison_orders_by_total_with_averages_and_shares(): void
    {
        $this->seedData();

        $this->get('/reports/vehicle-comparison?period=all')->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.name', 'Corolla')
            ->where('rows.0.total_cents', 24_000)
            ->where('rows.0.count', 2)
            ->where('rows.0.average_cents', 12_000)
            ->where('rows.0.share', 77.4)
            ->where('rows.1.name', 'Onix')
            ->where('totals.total_cents', 31_000)
            ->where('totals.count', 5)
            ->where('chart.labels', ['Corolla', 'Onix']));
    }

    public function test_by_category_report_and_donut_chart(): void
    {
        $this->seedData();

        $this->get('/reports/by-category?period=all')->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.name', 'Seguro')
            ->where('rows.0.total_cents', 18_000)
            ->where('rows.1.name', 'Combustível')
            ->where('rows.1.total_cents', 13_000)
            ->where('totals.total_cents', 31_000)
            ->where('chart.type', 'donut')
            ->where('chart.labels', ['Seguro', 'Combustível']));
    }

    public function test_combined_filters_vehicle_category_and_period(): void
    {
        $this->seedData();

        $this->get("/reports/expense-history?period=custom&from=2025-01-01&to=2026-12-31&vehicle_id={$this->onix->id}&category_id={$this->fuel->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('total_rows', 1)
                ->where('rows.0.amount_cents', 4_000)
                ->where('filters.vehicle_id', (string) $this->onix->id)
                ->where('filters.category_id', (string) $this->fuel->id)
                ->where('filters.from', '2025-01-01')
                ->where('applied.0.value', '01/01/2025 a 31/12/2026')
                ->where('applied.1.value', $this->onix->full_name)
                ->where('applied.2.value', 'Combustível'));
    }

    public function test_applied_filters_describe_scope_defaults(): void
    {
        $this->get('/reports/by-category')->assertInertia(fn (Assert $page) => $page
            ->where('applied.0.label', 'Período')
            ->where('applied.0.value', '01/07/2025 a 30/06/2026')
            ->where('applied.1.value', 'Veículos ativos')
            ->where('applied.2.value', 'Todas'));

        $this->get('/reports/by-category?period=all&include_sold=1')->assertInertia(fn (Assert $page) => $page
            ->where('applied.0.value', 'Todo o histórico')
            ->where('applied.1.value', 'Ativos e vendidos'));
    }

    public function test_sold_and_archived_vehicles_only_appear_when_requested(): void
    {
        $sold = $this->vehicle('Vendido', ['status' => VehicleStatus::Sold, 'sale_date' => '2025-12-31', 'sale_price_cents' => 1_000_000]);
        $archived = $this->vehicle('Arquivado');
        $archived->delete();
        $this->expense($this->onix, '2025-05-10', 1_000);
        $this->expense($sold, '2025-05-10', 2_000);
        $this->expense($archived, '2025-05-10', 4_000);

        $this->get('/reports/vehicle-comparison?period=all')->assertInertia(fn (Assert $page) => $page->where('totals.total_cents', 1_000));
        $this->get('/reports/vehicle-comparison?period=all&include_sold=1')->assertInertia(fn (Assert $page) => $page->where('totals.total_cents', 3_000));
        $this->get('/reports/vehicle-comparison?period=all&include_sold=1&include_archived=1')->assertInertia(fn (Assert $page) => $page->where('totals.total_cents', 7_000));
        $this->get("/reports/vehicle-comparison?period=all&vehicle_id={$sold->id}")->assertInertia(fn (Assert $page) => $page->where('totals.total_cents', 2_000));
    }

    public function test_report_and_dashboard_show_the_same_values_for_the_same_filter(): void
    {
        $this->seedData();

        $dashboard = $this->get('/?period=custom&from=2025-01-01&to=2026-12-31');
        $categories = null;
        $dashboard->assertInertia(function (Assert $page) use (&$categories) {
            $categories = $page->toArray()['props']['charts']['categories'];
            $page->etc();
        });

        $report = $this->get('/reports/by-category?period=custom&from=2025-01-01&to=2026-12-31');
        $report->assertInertia(fn (Assert $page) => $page
            ->where('rows', fn ($rows) => collect($rows)->map(fn ($row) => [$row['name'], $row['total_cents']])->all()
                === collect($categories)->map(fn ($row) => [$row['name'], $row['total_cents']])->all())
            ->where('totals.total_cents', 30_000));
    }

    public function test_empty_report_has_no_chart_and_no_invented_rows(): void
    {
        $this->get('/reports/by-category')->assertInertia(fn (Assert $page) => $page
            ->where('rows', [])
            ->where('chart', null)
            ->where('totals.total_cents', 0)
            ->where('completeness.has_data', false));
    }

    public function test_completeness_notice_for_periods_before_tracking(): void
    {
        $this->vehicle('Novo', ['acquisition_date' => '2022-01-01', 'tracking_start_date' => '2025-06-01']);

        $this->get('/reports/by-category?period=custom&from=2024-01-01&to=2024-12-31')->assertInertia(fn (Assert $page) => $page
            ->where('completeness.is_complete', false)
            ->where('completeness.incomplete_vehicles.0.name', 'Novo'));
    }

    public function test_registry_keys_are_unique_and_every_report_has_typed_columns(): void
    {
        $registry = app(ReportRegistry::class);
        $keys = array_map(fn ($report) => $report->key(), $registry->all());
        $filters = new ReportFilters(Period::all());

        $this->assertSame($keys, array_unique($keys));

        foreach ($registry->all() as $report) {
            $rows = $report->rows($filters);

            foreach ($report->columns($filters, $rows) as $column) {
                $this->assertArrayHasKey('key', $column);
                $this->assertContains($column['type'], ['text', 'date', 'month', 'year', 'number', 'currency', 'currency_decimal', 'decimal2', 'decimal3', 'percent', 'km']);
            }
        }
    }
}
