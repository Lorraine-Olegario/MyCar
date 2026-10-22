<?php

namespace Tests\Feature;

use App\Domain\Reporting\ReportCsv;
use App\Domain\Reporting\ReportRegistry;
use App\Enums\VehicleStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Fueling;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function vehicle(string $name = 'Onix', array $overrides = []): Vehicle
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
            'expense_category_id' => ($category ?? ExpenseCategory::factory()->create(['name' => 'Combustível']))->id,
            'occurred_on' => $date,
            'amount_cents' => $cents,
        ], $overrides));
    }

    /**
     * @return array{0: string, 1: list<list<string>>}
     */
    private function csv(string $uri): array
    {
        $uri = preg_replace('#^(/reports/[^/?]+)(?![^/?]|/csv)#', '$1/csv', $uri);
        $response = $this->get($uri)->assertOk();
        $content = $response->streamedContent();

        $lines = array_values(array_filter(explode("\r\n", $content), fn ($line) => $line !== ''));

        return [$content, array_map(fn ($line) => str_getcsv($line, ';', '"', ''), $lines)];
    }

    public function test_file_is_utf8_with_bom_semicolon_separated_with_clear_headers(): void
    {
        $this->expense($this->vehicle('Fusca São João'), '2024-02-29', 123_456, ExpenseCategory::factory()->create(['name' => 'Manutenção']), ['description' => 'Troca de óleo', 'vendor' => 'Oficina Ação', 'payment_method' => 'pix']);

        [$content, $rows] = $this->csv('/reports/expense-history?period=all');

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertTrue(mb_check_encoding($content, 'UTF-8'));
        $this->assertSame(['Data', 'Veículo', 'Categoria', 'Descrição', 'Fornecedor', 'Pagamento', 'Valor (R$)'], array_map(fn ($cell) => ltrim($cell, "\xEF\xBB\xBF"), $rows[0]));
        $this->assertSame(['29/02/2024', 'Fusca São João', 'Manutenção', 'Troca de óleo', 'Oficina Ação', 'Pix', '1234,56'], $rows[1]);
        $this->assertStringContainsString(';', $content);
        $this->assertStringNotContainsString("\t", $content);
    }

    public function test_response_headers_and_filename_carry_report_and_period(): void
    {
        $this->expense($this->vehicle(), '2025-02-10', 1_000);

        $response = $this->get('/reports/by-category/csv?period=custom&from=2025-01-01&to=2025-12-31')->assertOk();

        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('UTF-8', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('mycar-by-category-2025-01-01_a_2025-12-31.csv', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('mycar-expense-history-todo-o-historico.csv', (string) $this->get('/reports/expense-history/csv?period=all')->headers->get('content-disposition'));
        $this->assertStringContainsString('mycar-ownership-cost-2026-06-15.csv', (string) $this->get('/reports/ownership-cost/csv')->headers->get('content-disposition'));
    }

    public function test_export_respects_vehicle_category_and_period_filters(): void
    {
        $onix = $this->vehicle('Onix');
        $corolla = $this->vehicle('Corolla');
        $fuel = ExpenseCategory::factory()->create(['name' => 'Combustível']);
        $insurance = ExpenseCategory::factory()->create(['name' => 'Seguro']);

        $this->expense($onix, '2025-03-10', 1_000, $fuel, ['description' => 'A']);
        $this->expense($onix, '2025-03-11', 2_000, $insurance, ['description' => 'B']);
        $this->expense($onix, '2024-03-10', 4_000, $fuel, ['description' => 'C']);
        $this->expense($corolla, '2025-03-10', 8_000, $fuel, ['description' => 'D']);

        [, $rows] = $this->csv("/reports/expense-history?period=custom&from=2025-01-01&to=2025-12-31&vehicle_id={$onix->id}&category_id={$fuel->id}");

        $this->assertCount(2, $rows);
        $this->assertSame('A', $rows[1][3]);
    }

    public function test_export_contains_every_row_of_the_filter_not_only_the_first_page(): void
    {
        $vehicle = $this->vehicle();
        $category = ExpenseCategory::factory()->create();

        foreach (range(1, 45) as $i) {
            $this->expense($vehicle, '2025-01-10', $i * 100, $category, ['description' => "Despesa {$i}"]);
        }

        [, $rows] = $this->csv('/reports/expense-history?period=all');

        $this->assertCount(46, $rows);
    }

    public function test_numbers_use_comma_decimals_dates_are_dd_mm_yyyy_and_nulls_are_empty(): void
    {
        $vehicle = $this->vehicle('Onix');
        Fueling::factory()->create(['vehicle_id' => $vehicle->id, 'fueled_on' => '2026-01-01', 'odometer' => 10_000, 'liters' => 40, 'price_per_liter' => 5.899, 'total_cents' => 23_596, 'full_tank' => true]);
        Fueling::factory()->create(['vehicle_id' => $vehicle->id, 'fueled_on' => '2026-01-20', 'odometer' => 10_400, 'liters' => 32, 'price_per_liter' => 6.0, 'total_cents' => 19_200, 'full_tank' => true]);

        [, $rows] = $this->csv('/reports/fuel-history');

        $this->assertSame(['Data', 'Veículo', 'Odômetro (km)', 'Litros', 'Preço por litro (R$)', 'Valor (R$)', 'Tanque', 'Km percorridos (km)', 'Consumo (km/l)', 'Custo por km (R$)'], array_map(fn ($cell) => ltrim($cell, "\xEF\xBB\xBF"), $rows[0]));
        $this->assertSame(['20/01/2026', 'Onix', '10400', '32,000', '6,000', '192,00', 'Cheio', '400', '12,50', '0,480'], $rows[1]);
        $this->assertSame(['01/01/2026', 'Onix', '10000', '40,000', '5,899', '235,96', 'Cheio', '', '', ''], $rows[2]);
    }

    public function test_formula_injection_is_neutralized_and_special_characters_are_escaped(): void
    {
        $this->expense($this->vehicle('=HYPERLINK("http://x")'), '2025-01-10', 1_000, null, ['description' => "Linha; com \"aspas\"\ne quebra", 'vendor' => '+cmd|calc']);

        [$content, $rows] = $this->csv('/reports/expense-history?period=all');

        $this->assertSame("'=HYPERLINK(\"http://x\")", $rows[1][1]);
        $this->assertSame('Linha; com "aspas" e quebra', $rows[1][3]);
        $this->assertSame("'+cmd|calc", $rows[1][4]);
        $this->assertCount(2, explode("\r\n", rtrim($content, "\r\n")));
    }

    public function test_no_sensitive_columns_are_exported(): void
    {
        $this->expense($this->vehicle(), '2025-01-10', 1_000, null, ['notes' => 'segredo', 'receipt_path' => 'expenses/1/x.pdf']);

        [$content] = $this->csv('/reports/expense-history?period=all');

        $this->assertStringNotContainsString('segredo', $content);
        $this->assertStringNotContainsString('expenses/1', $content);
    }

    public function test_empty_report_exports_only_the_header(): void
    {
        [, $rows] = $this->csv('/reports/by-category');

        $this->assertCount(1, $rows);
    }

    public function test_every_registered_report_exports(): void
    {
        $this->expense($this->vehicle(), '2026-05-10', 1_000);

        foreach (app(ReportRegistry::class)->all() as $report) {
            [$content, $rows] = $this->csv('/reports/'.$report->key().'?period=all');

            $this->assertStringStartsWith("\xEF\xBB\xBF", $content, $report->key());
            $this->assertGreaterThanOrEqual(1, count($rows), $report->key());
        }
    }

    public function test_unknown_report_is_404(): void
    {
        $this->get('/reports/nope/csv')->assertNotFound();
    }

    public function test_cell_formatting_helper(): void
    {
        $csv = new ReportCsv;

        $this->assertSame('0,18', $csv->cell('currency', 18));
        $this->assertSame('0,184', $csv->cell('currency_decimal', 18.4));
        $this->assertSame('03/2026', $csv->cell('month', '2026-03'));
        $this->assertSame('33,3', $csv->cell('percent', 33.333));
        $this->assertSame('', $csv->cell('currency', null));
        $this->assertSame('0,00', $csv->cell('currency', 0));
    }
}
