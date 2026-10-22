<?php

namespace Tests\Feature;

use App\Domain\Fipe\FipeFleetSummary;
use App\Enums\VehicleStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FipeQuote;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FipeReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['services.fipe.stale_days' => 35]);
        Carbon::setTestNow('2026-06-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function vehicle(string $name, array $overrides = []): Vehicle
    {
        return Vehicle::factory()->create(array_merge(['name' => $name, 'status' => VehicleStatus::Active, 'acquisition_date' => '2020-01-10', 'tracking_start_date' => '2020-01-10'], $overrides));
    }

    /**
     * Grava a cotação como o serviço faz: histórico + campos atuais do veículo.
     */
    private function quote(Vehicle $vehicle, string $consultedAt, int $cents, string $reference): FipeQuote
    {
        $quote = FipeQuote::factory()->create(['vehicle_id' => $vehicle->id, 'consulted_at' => $consultedAt, 'value_cents' => $cents, 'reference_month' => $reference]);
        $vehicle->forceFill(['fipe_value_cents' => $cents, 'fipe_reference_month' => $reference, 'fipe_consulted_at' => $consultedAt])->save();

        return $quote;
    }

    public function test_fleet_summary_only_counts_active_vehicles_and_current_quotes(): void
    {
        $fresh = $this->vehicle('Atual');
        $stale = $this->vehicle('Antigo');
        $never = $this->vehicle('Sem consulta');
        $sold = $this->vehicle('Vendido', ['status' => VehicleStatus::Sold, 'sale_date' => '2026-01-01', 'sale_price_cents' => 1]);
        $archived = $this->vehicle('Arquivado');
        $archived->delete();

        $this->quote($fresh, '2026-06-01 09:00:00', 6_000_000, '2026-06');
        $this->quote($stale, '2026-04-01 09:00:00', 5_000_000, '2026-04');
        $this->quote($sold, '2026-06-01 09:00:00', 9_000_000, '2026-06');
        $this->quote($archived, '2026-06-01 09:00:00', 8_000_000, '2026-06');

        $summary = app(FipeFleetSummary::class)->summarize();

        $this->assertSame(6_000_000, $summary['total_cents']);
        $this->assertSame(3, $summary['vehicles']);
        $this->assertSame(1, $summary['with_quote']);
        $this->assertSame(1, $summary['stale']);
        $this->assertSame(1, $summary['missing']);
        $this->assertNotNull($never);
    }

    public function test_fleet_total_is_null_when_no_quote_is_current(): void
    {
        $this->quote($this->vehicle('Antigo'), '2026-01-01 09:00:00', 5_000_000, '2026-01');

        $summary = app(FipeFleetSummary::class)->summarize();

        $this->assertNull($summary['total_cents']);
        $this->assertSame(1, $summary['stale']);
    }

    public function test_staleness_boundary_is_35_days(): void
    {
        $edge = $this->vehicle('Limite');
        $this->quote($edge, '2026-05-11 10:00:00', 1_000_000, '2026-05');

        $this->assertSame(1, app(FipeFleetSummary::class)->summarize()['with_quote']);

        Carbon::setTestNow('2026-06-16 10:00:01');
        $this->assertSame(1, app(FipeFleetSummary::class)->summarize()['stale']);
    }

    public function test_dashboard_exposes_fleet_fipe_separately_from_spending(): void
    {
        $a = $this->vehicle('A');
        $b = $this->vehicle('B');
        $this->quote($a, '2026-06-10 09:00:00', 6_000_000, '2026-06');
        $this->quote($b, '2026-03-10 09:00:00', 4_000_000, '2026-03');
        Expense::factory()->create(['vehicle_id' => $a->id, 'expense_category_id' => ExpenseCategory::factory()->create()->id, 'occurred_on' => '2026-06-05', 'amount_cents' => 1_000]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('kpis.fipe_cents', 6_000_000)
            ->where('fipe', ['total_cents' => 6_000_000, 'vehicles' => 2, 'with_quote' => 1, 'stale' => 1, 'missing' => 0])
            ->where('kpis.total_cents', 1_000)
            ->where('kpis.year_cents', 1_000)
            ->where('kpis.month_cents', 1_000));

        $this->get("/?vehicle_id={$b->id}")->assertInertia(fn (Assert $page) => $page
            ->where('kpis.fipe_cents', null)
            ->where('fipe.stale', 1));
    }

    public function test_dashboard_without_quotes_keeps_the_placeholder(): void
    {
        $this->vehicle('A');

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('kpis.fipe_cents', null)
            ->where('fipe.missing', 1));
    }

    public function test_vehicle_page_exposes_current_value_history_and_variation_without_touching_expenses(): void
    {
        $vehicle = $this->vehicle('A', ['purchase_price_cents' => 7_000_000]);
        $this->quote($vehicle, '2026-04-10 09:00:00', 6_500_000, '2026-04');
        $this->quote($vehicle, '2026-06-10 09:00:00', 6_000_000, '2026-06');

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $page) => $page
            ->where('fipe.snapshot.value_cents', 6_000_000)
            ->where('fipe.variation.change_cents', -500_000)
            ->where('fipe.variation.change_percent', -7.7)
            ->where('fipe.variation.previous_reference_month', '2026-04')
            ->has('fipe.quotes', 2)
            ->where('financial.summary.operating_cents', 0)
            ->where('financial.summary.tco_cents', 7_000_000));
    }

    public function test_vehicle_with_a_single_quote_has_no_variation(): void
    {
        $vehicle = $this->vehicle('A');
        $this->quote($vehicle, '2026-06-10 09:00:00', 6_000_000, '2026-06');

        $this->get("/vehicles/{$vehicle->id}")->assertInertia(fn (Assert $page) => $page->where('fipe.variation', null));
    }

    public function test_report_8_shows_the_real_evolution_with_variation_and_gaps(): void
    {
        $vehicle = $this->vehicle('Onix');
        $this->quote($vehicle, '2026-01-10 09:00:00', 6_500_000, '2026-01');
        $this->quote($vehicle, '2026-02-10 09:00:00', 6_400_000, '2026-02');
        $this->quote($vehicle, '2026-05-10 09:00:00', 6_000_000, '2026-05');

        $this->get('/reports/fipe-evolution')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('report.key', 'fipe-evolution')
            ->where('total_rows', 3)
            ->where('rows.0.reference_month', '2026-05')
            ->where('rows.0.value_cents', 6_000_000)
            ->where('rows.0.change_cents', -400_000)
            ->where('rows.0.change_percent', -6.3)
            ->where('rows.0.gap', fn ($gap) => str_contains($gap, 'Lacuna de 2 meses'))
            ->where('rows.1.change_cents', -100_000)
            ->where('rows.1.gap', null)
            ->where('rows.2.change_cents', null)
            ->where('rows.2.change_percent', null)
            ->where('chart.type', 'line')
            ->where('chart.labels', ['2026-01', '2026-02', '2026-05'])
            ->where('chart.series.0.values', [6_500_000, 6_400_000, 6_000_000]));
    }

    public function test_report_8_filters_by_period_but_variation_uses_the_real_previous_quote(): void
    {
        $vehicle = $this->vehicle('Onix');
        $this->quote($vehicle, '2026-01-10 09:00:00', 6_500_000, '2026-01');
        $this->quote($vehicle, '2026-04-10 09:00:00', 6_000_000, '2026-04');

        $this->get('/reports/fipe-evolution?period=custom&from=2026-03-01&to=2026-12-31')->assertInertia(fn (Assert $page) => $page
            ->where('total_rows', 1)
            ->where('rows.0.change_cents', -500_000)
            ->where('rows.0.gap', fn ($gap) => str_contains($gap, 'Lacuna de 2 meses')));
    }

    public function test_report_8_is_scoped_by_vehicle_and_status_and_never_invents_data(): void
    {
        $a = $this->vehicle('A');
        $sold = $this->vehicle('Vendido', ['status' => VehicleStatus::Sold, 'sale_date' => '2026-01-01', 'sale_price_cents' => 1]);
        $this->quote($a, '2026-06-01 09:00:00', 1_000_000, '2026-06');
        $this->quote($sold, '2026-06-01 09:00:00', 2_000_000, '2026-06');

        $this->get('/reports/fipe-evolution')->assertInertia(fn (Assert $page) => $page->where('total_rows', 1)->where('rows.0.vehicle', 'A'));
        $this->get('/reports/fipe-evolution?include_sold=1')->assertInertia(fn (Assert $page) => $page->where('total_rows', 2));
        $this->get("/reports/fipe-evolution?vehicle_id={$sold->id}")->assertInertia(fn (Assert $page) => $page->where('total_rows', 1)->where('rows.0.vehicle', 'Vendido'));

        $this->vehicle('Sem consulta');
        $this->get('/reports/fipe-evolution?period=custom&from=2020-01-01&to=2020-12-31')->assertInertia(fn (Assert $page) => $page
            ->where('rows', [])
            ->where('chart', null));
    }

    public function test_fipe_never_enters_expense_reports_or_totals(): void
    {
        $vehicle = $this->vehicle('A', ['purchase_price_cents' => 1_000_000]);
        $this->quote($vehicle, '2026-06-10 09:00:00', 9_999_999, '2026-06');

        $this->get('/reports/by-category?period=all')->assertInertia(fn (Assert $page) => $page->where('totals.total_cents', 0));
        $this->get('/reports/ownership-cost')->assertInertia(fn (Assert $page) => $page
            ->where('totals.tco_cents', 1_000_000)
            ->where('columns', fn ($columns) => ! collect($columns)->pluck('key')->contains(fn ($key) => str_contains($key, 'fipe'))));
    }

    public function test_report_8_exports_to_csv_with_month_and_percent_formats(): void
    {
        $vehicle = $this->vehicle('Onix');
        $this->quote($vehicle, '2026-01-10 09:00:00', 6_500_000, '2026-01');
        $this->quote($vehicle, '2026-02-10 09:00:00', 6_400_000, '2026-02');

        $content = $this->get('/reports/fipe-evolution/csv')->streamedContent();
        $lines = array_values(array_filter(explode("\r\n", $content)));

        $this->assertStringContainsString('Valor FIPE (R$)', $lines[0]);
        $this->assertStringContainsString('Variação % (%)', $lines[0]);
        $this->assertStringContainsString('10/02/2026;Onix;02/2026;', $lines[1]);
        $this->assertStringContainsString('64000,00;-1000,00;-1,5', $lines[1]);
    }
}
