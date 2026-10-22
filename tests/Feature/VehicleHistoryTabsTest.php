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
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VehicleHistoryTabsTest extends TestCase
{
    use RefreshDatabase;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-06-15');
        $this->vehicle = Vehicle::factory()->create([
            'status' => VehicleStatus::Active,
            'acquisition_date' => '2020-01-10',
            'tracking_start_date' => '2020-01-10',
            'odometer' => 40_000,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function expense(string $date, int $cents, ?ExpenseCategory $category = null, ?Vehicle $vehicle = null): Expense
    {
        return Expense::factory()->create([
            'vehicle_id' => ($vehicle ?? $this->vehicle)->id,
            'expense_category_id' => ($category ?? ExpenseCategory::factory()->create())->id,
            'occurred_on' => $date,
            'amount_cents' => $cents,
        ]);
    }

    private function show(string $query = ''): TestResponse
    {
        return $this->get("/vehicles/{$this->vehicle->id}".($query !== '' ? '?'.$query : ''));
    }

    public function test_default_tab_is_expenses_with_all_history_and_old_data_reachable(): void
    {
        $this->expense('2021-03-10', 1_000);
        $this->expense('2026-05-10', 2_000);
        $this->expense('2026-05-11', 4_000, vehicle: Vehicle::factory()->create());

        $this->show()->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('history.tab', 'expenses')
            ->where('history.filters.scope', 'all')
            ->where('history.data.total_cents', 3_000)
            ->where('history.data.items.total', 2)
            ->where('history.data.items.data.0.occurred_on', '2026-05-10')
            ->where('history.data.items.data.1.occurred_on', '2021-03-10'));
    }

    public function test_expenses_paginate_by_fifteen_and_keep_the_query(): void
    {
        foreach (range(1, 17) as $day) {
            $this->expense('2025-01-'.str_pad((string) (($day % 28) + 1), 2, '0', STR_PAD_LEFT), 100);
        }

        $this->show('tab=expenses&year=2025')->assertInertia(fn (Assert $page) => $page
            ->has('history.data.items.data', 15)
            ->where('history.data.items.total', 17)
            ->where('history.data.items.next_page_url', fn ($url) => str_contains($url, 'tab=expenses') && str_contains($url, 'year=2025')));

        $this->show('page=2')->assertInertia(fn (Assert $page) => $page->has('history.data.items.data', 2));
    }

    public function test_period_scope_selected_uses_the_financial_year_and_month(): void
    {
        $this->expense('2025-03-10', 1_000);
        $this->expense('2025-04-10', 2_000);
        $this->expense('2024-03-10', 4_000);

        $this->show('year=2025&scope=selected')->assertInertia(fn (Assert $page) => $page
            ->where('history.filters.scope', 'selected')
            ->where('history.data.total_cents', 3_000));

        $this->show('year=2025&month=4&scope=selected')->assertInertia(fn (Assert $page) => $page
            ->where('history.data.total_cents', 2_000));

        $this->show('scope=custom&from=2024-01-01&to=2024-12-31')->assertInertia(fn (Assert $page) => $page
            ->where('history.filters.from', '2024-01-01')
            ->where('history.data.total_cents', 4_000));

        $this->show('scope=custom&from=bad&to=worse')->assertInertia(fn (Assert $page) => $page
            ->where('history.filters.scope', 'all'));
    }

    public function test_category_filter_applies_to_expenses_and_maintenances(): void
    {
        $fuel = ExpenseCategory::factory()->create();
        $this->expense('2025-01-10', 1_000, $fuel);
        $this->expense('2025-01-11', 2_000);

        $this->show("category_id={$fuel->id}")->assertInertia(fn (Assert $page) => $page
            ->where('history.filters.category_id', (string) $fuel->id)
            ->where('history.data.total_cents', 1_000)
            ->has('history.categories', 2));
    }

    public function test_fuelings_tab_has_consumption_and_insufficient_data_messages(): void
    {
        $f = fn (string $date, int $odometer, float $liters, int $cents, bool $full = true) => Fueling::factory()->create([
            'vehicle_id' => $this->vehicle->id, 'fueled_on' => $date, 'odometer' => $odometer, 'liters' => $liters,
            'total_cents' => $cents, 'price_per_liter' => round($cents / 100 / $liters, 3), 'full_tank' => $full,
        ]);
        $first = $f('2026-01-01', 10_000, 40, 24_000);
        $second = $f('2026-01-20', 10_400, 32, 19_200);

        $this->show('tab=fuelings')->assertInertia(fn (Assert $page) => $page
            ->where('history.tab', 'fuelings')
            ->where('history.data.items.data.0.id', $second->id)
            ->where('history.data.items.data.0.stats.km_per_liter', 12.5)
            ->where('history.data.items.data.1.id', $first->id)
            ->where('history.data.items.data.1.stats.km_per_liter', null)
            ->where('history.data.summary.average_km_per_liter', 12.5)
            ->where('history.data.summary.total_cents', 43_200));

        $this->show('tab=fuelings&scope=custom&from=2026-01-01&to=2026-01-10')->assertInertia(fn (Assert $page) => $page
            ->where('history.data.items.total', 1)
            ->where('history.data.summary.average_km_per_liter', null));
    }

    public function test_maintenances_tab_lists_history_with_due_flags_only_on_the_latest(): void
    {
        $category = ExpenseCategory::factory()->create();
        $old = Maintenance::factory()->create(['expense_id' => $this->expense('2025-01-10', 20_000, $category)->id, 'next_due_on' => '2025-07-10']);
        $new = Maintenance::factory()->create(['expense_id' => $this->expense('2026-06-01', 30_000, $category)->id, 'next_due_on' => '2026-07-01']);

        $this->show('tab=maintenances')->assertInertia(fn (Assert $page) => $page
            ->where('history.data.items.data.0.id', $new->id)
            ->where('history.data.items.data.0.due.status', 'due_soon')
            ->where('history.data.items.data.1.id', $old->id)
            ->where('history.data.items.data.1.due.status', 'none')
            ->where('history.data.items.data.0.amount_cents', 30_000));
    }

    public function test_recurring_tab_lists_models_with_next_due(): void
    {
        app(RecurringExpenseService::class)->create(RecurringExpense::factory()->make([
            'vehicle_id' => $this->vehicle->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'description' => 'Seguro',
            'frequency' => 'annual',
            'start_date' => '2026-06-01',
            'due_day' => 20,
            'expected_amount_cents' => 120_000,
        ])->getAttributes());

        $this->show('tab=recurring')->assertInertia(fn (Assert $page) => $page
            ->has('history.data.items', 1)
            ->where('history.data.items.0.description', 'Seguro')
            ->where('history.data.items.0.monthly_estimate_cents', 10_000)
            ->where('history.data.items.0.next_due_date', fn ($date) => str_starts_with($date, '2026-06-20')));
    }

    public function test_dues_tab_highlights_overdue_and_upcoming_from_recurrences_and_maintenance(): void
    {
        $service = app(RecurringExpenseService::class);
        $recurring = $service->create(RecurringExpense::factory()->make([
            'vehicle_id' => $this->vehicle->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'description' => 'IPVA',
            'frequency' => 'monthly',
            'start_date' => '2026-05-01',
            'due_day' => 10,
        ])->getAttributes());

        $maintenance = Maintenance::factory()->create([
            'expense_id' => $this->expense('2026-05-01', 30_000)->id,
            'next_due_on' => '2026-06-20',
        ]);

        $this->assertNotNull($recurring);

        $this->show('tab=dues')->assertInertia(fn (Assert $page) => $page
            ->where('history.tab', 'dues')
            ->where('history.data.items.total', fn ($total) => $total >= 3)
            ->has('history.upcoming', 5)
            ->where('history.data.items.data', fn ($items) => collect($items)->contains(fn ($item) => $item['kind'] === 'maintenance' && $item['highlight'] === 'due_soon')
                && collect($items)->contains(fn ($item) => $item['kind'] === 'recurring' && $item['highlight'] === 'due_soon')));

        $this->assertNotNull($maintenance);
    }

    public function test_fipe_tab_is_a_placeholder_and_invalid_tabs_fall_back(): void
    {
        $this->show('tab=fipe')->assertInertia(fn (Assert $page) => $page->where('history.tab', 'fipe')->where('history.data', null));
        $this->show('tab=hack')->assertInertia(fn (Assert $page) => $page->where('history.tab', 'expenses'));
    }

    public function test_highlighted_upcoming_list_is_scoped_to_the_vehicle(): void
    {
        $other = Vehicle::factory()->create();
        app(RecurringExpenseService::class)->create(RecurringExpense::factory()->make([
            'vehicle_id' => $other->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'start_date' => '2026-06-01',
            'due_day' => 20,
        ])->getAttributes());

        $this->show()->assertInertia(fn (Assert $page) => $page->where('history.upcoming', []));
    }

    public function test_edit_deep_links_expose_the_record_for_the_existing_forms(): void
    {
        $expense = $this->expense('2026-05-10', 2_000);
        $fueling = Fueling::factory()->create(['vehicle_id' => $this->vehicle->id]);
        $maintenance = Maintenance::factory()->create(['expense_id' => $this->expense('2026-05-11', 3_000)->id]);
        $recurring = RecurringExpense::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->get("/expenses?edit={$expense->id}")->assertInertia(fn (Assert $page) => $page->where('editing.id', $expense->id));
        $this->get("/fuelings?edit={$fueling->id}")->assertInertia(fn (Assert $page) => $page->where('editing.id', $fueling->id));
        $this->get("/maintenances?edit={$maintenance->id}")->assertInertia(fn (Assert $page) => $page
            ->where('editing.id', $maintenance->id)
            ->where('editing.expense.amount_cents', 3_000));
        $this->get("/recurring?edit={$recurring->id}")->assertInertia(fn (Assert $page) => $page->where('editing_id', $recurring->id));

        $this->get('/expenses')->assertInertia(fn (Assert $page) => $page->where('editing', null));
    }
}
