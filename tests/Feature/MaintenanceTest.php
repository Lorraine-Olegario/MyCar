<?php

namespace Tests\Feature;

use App\Domain\Maintenance\MaintenanceDueEvaluator;
use App\Domain\Maintenance\MaintenanceService;
use App\Enums\MaintenanceType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Database\Seeders\ExpenseCategorySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private Vehicle $vehicle;

    private ExpenseCategory $oil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-06-15');
        $this->seed(ExpenseCategorySeeder::class);

        $this->vehicle = Vehicle::factory()->create(['odometer' => 40_000]);
        $this->oil = ExpenseCategory::where('slug', 'troca-de-oleo-e-filtros')->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $this->vehicle->id,
            'expense_category_id' => $this->oil->id,
            'description' => 'Troca de óleo e filtro',
            'amount_cents' => 35_000,
            'occurred_on' => '2026-06-10',
            'type' => 'preventive',
            'odometer' => 40_500,
            'next_due_on' => '2026-12-10',
            'next_due_odometer' => 50_500,
        ], $overrides);
    }

    public function test_maintenance_is_created_together_with_its_expense(): void
    {
        $this->post('/maintenances', $this->payload(['vendor' => 'Oficina X', 'payment_method' => 'pix']))
            ->assertRedirect('/maintenances')
            ->assertSessionHasNoErrors();

        $maintenance = Maintenance::with('expense')->first();
        $expense = $maintenance->expense;

        $this->assertSame(1, Expense::count());
        $this->assertSame(35_000, $expense->amount_cents);
        $this->assertSame('2026-06-10', $expense->occurred_on->toDateString());
        $this->assertSame($this->oil->id, $expense->expense_category_id);
        $this->assertSame('Oficina X', $expense->vendor);
        $this->assertSame(40_500, $expense->odometer);
        $this->assertSame(MaintenanceType::Preventive, $maintenance->type);
        $this->assertSame('2026-12-10', $maintenance->next_due_on->toDateString());
        $this->assertSame(50_500, $maintenance->next_due_odometer);
        $this->assertSame(40_500, $this->vehicle->fresh()->odometer);
    }

    public function test_nothing_is_persisted_when_the_expense_fails(): void
    {
        $this->post('/maintenances', $this->payload(['amount_cents' => 0]))->assertSessionHasErrors('amount_cents');

        $this->assertSame(0, Expense::count());
        $this->assertSame(0, Maintenance::count());
    }

    public function test_transaction_rolls_back_when_the_maintenance_insert_fails(): void
    {
        $service = app(MaintenanceService::class);

        try {
            $service->create($this->payload(['type' => null]));
            $this->fail('A manutenção sem tipo deveria falhar.');
        } catch (QueryException) {
            $this->assertSame(0, Expense::count());
            $this->assertSame(0, Maintenance::count());
        }
    }

    public function test_category_must_be_a_maintenance_category(): void
    {
        $insurance = ExpenseCategory::where('slug', 'seguro')->first();

        $this->post('/maintenances', $this->payload(['expense_category_id' => $insurance->id]))
            ->assertSessionHasErrors('expense_category_id');
        $this->post('/maintenances', $this->payload(['expense_category_id' => ExpenseCategory::factory()->create()->id]))
            ->assertSessionHasErrors('expense_category_id');
        $this->assertSame(0, Maintenance::count());
    }

    public function test_validation_of_next_due_fields(): void
    {
        $this->post('/maintenances', $this->payload(['next_due_on' => '2026-06-01']))
            ->assertSessionHasErrors(['next_due_on' => 'A próxima troca deve ser posterior à data da manutenção.']);
        $this->post('/maintenances', $this->payload(['next_due_odometer' => 40_000]))
            ->assertSessionHasErrors('next_due_odometer');
        $this->post('/maintenances', $this->payload(['next_due_on' => '', 'next_due_odometer' => '', 'odometer' => '']))
            ->assertSessionHasNoErrors();
    }

    public function test_one_to_one_link_is_enforced_by_the_database(): void
    {
        $this->post('/maintenances', $this->payload());
        $maintenance = Maintenance::first();

        $this->expectException(QueryException::class);

        Maintenance::create(['vehicle_id' => $this->vehicle->id, 'expense_id' => $maintenance->expense_id, 'type' => 'corrective']);
    }

    public function test_update_keeps_expense_and_maintenance_in_sync(): void
    {
        $this->post('/maintenances', $this->payload());
        $maintenance = Maintenance::first();

        $this->put("/maintenances/{$maintenance->id}", $this->payload([
            'description' => 'Óleo sintético',
            'amount_cents' => 42_000,
            'occurred_on' => '2026-06-12',
            'type' => 'corrective',
            'next_due_on' => '2027-01-10',
        ]))->assertRedirect('/maintenances')->assertSessionHasNoErrors();

        $this->assertSame(1, Expense::count());
        $this->assertSame(1, Maintenance::count());
        $this->assertSame('Óleo sintético', $maintenance->expense->fresh()->description);
        $this->assertSame(42_000, $maintenance->expense->fresh()->amount_cents);
        $this->assertSame('2026-06-12', $maintenance->expense->fresh()->occurred_on->toDateString());
        $this->assertSame(MaintenanceType::Corrective, $maintenance->fresh()->type);
        $this->assertSame('2027-01-10', $maintenance->fresh()->next_due_on->toDateString());
    }

    public function test_deleting_a_maintenance_removes_its_expense_and_there_are_no_orphans(): void
    {
        $this->post('/maintenances', $this->payload());
        $maintenance = Maintenance::first();

        $this->delete("/maintenances/{$maintenance->id}")->assertRedirect('/maintenances');

        $this->assertSame(0, Maintenance::count());
        $this->assertSame(0, Expense::count());
    }

    public function test_expense_of_a_maintenance_cannot_be_deleted_directly_nor_moved_out_of_maintenance_categories(): void
    {
        $this->post('/maintenances', $this->payload());
        $expense = Maintenance::first()->expense;

        $this->delete("/expenses/{$expense->id}")->assertSessionHas('error');
        $this->assertModelExists($expense);

        $this->put("/expenses/{$expense->id}", [
            'vehicle_id' => $this->vehicle->id,
            'expense_category_id' => ExpenseCategory::where('slug', 'seguro')->first()->id,
            'description' => $expense->description,
            'amount_cents' => $expense->amount_cents,
            'occurred_on' => '2026-06-10',
        ])->assertSessionHasErrors('expense_category_id');
    }

    public function test_deleting_the_expense_at_database_level_is_restricted(): void
    {
        $this->post('/maintenances', $this->payload());

        $this->expectException(QueryException::class);

        Maintenance::first()->expense->delete();
    }

    public function test_history_lists_cost_once_and_flags_only_the_latest_due_by_category(): void
    {
        $this->post('/maintenances', $this->payload(['occurred_on' => '2025-12-01', 'next_due_on' => '2026-06-01', 'next_due_odometer' => null, 'odometer' => 35_000]));
        $this->post('/maintenances', $this->payload(['occurred_on' => '2026-06-10', 'next_due_on' => '2026-12-10', 'next_due_odometer' => 40_900]));

        $this->get('/maintenances')->assertInertia(fn (Assert $page) => $page
            ->component('Maintenances/Index')
            ->has('maintenances.data', 2)
            ->where('maintenances.data.0.expense.occurred_on', '2026-06-10')
            ->where('maintenances.data.0.due.status', 'due_soon')
            ->where('maintenances.data.0.due.reason', 'odometer')
            ->where('maintenances.data.1.due.status', 'none')
            ->has('options.categories', 7));

        $this->assertSame(70_000, (int) Expense::sum('amount_cents'));
    }

    public function test_history_filters_by_vehicle(): void
    {
        $other = Vehicle::factory()->create();
        $this->post('/maintenances', $this->payload());
        $this->post('/maintenances', $this->payload(['vehicle_id' => $other->id]));

        $this->get("/maintenances?vehicle_id={$other->id}")->assertInertia(fn (Assert $page) => $page
            ->has('maintenances.data', 1)
            ->where('maintenances.data.0.vehicle_id', $other->id));
    }

    public function test_due_evaluator_by_date_and_odometer(): void
    {
        $evaluator = new MaintenanceDueEvaluator;
        $today = Carbon::parse('2026-06-15');

        $make = fn (?string $date, ?int $km) => new Maintenance(['next_due_on' => $date, 'next_due_odometer' => $km]);

        $this->assertSame('none', $evaluator->evaluate($make(null, null), 40_000, $today)['status']);
        $this->assertSame('ok', $evaluator->evaluate($make('2026-12-15', 50_000), 40_000, $today)['status']);
        $this->assertSame('due_soon', $evaluator->evaluate($make('2026-07-10', null), 40_000, $today)['status']);
        $this->assertSame('due_soon', $evaluator->evaluate($make(null, 40_800), 40_000, $today)['status']);
        $this->assertSame('overdue', $evaluator->evaluate($make('2026-06-14', null), 40_000, $today)['status']);
        $this->assertSame('overdue', $evaluator->evaluate($make('2026-12-01', 39_900), 40_000, $today)['status']);
        $this->assertSame('none', $evaluator->evaluate($make(null, 41_000), null, $today)['status']);
    }
}
