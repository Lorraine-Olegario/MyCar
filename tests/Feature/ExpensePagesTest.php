<?php

namespace Tests\Feature;

use App\Domain\Expenses\ExpenseReceiptStorage;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpensePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake(ExpenseReceiptStorage::DISK);
    }

    public function test_index_sends_the_props_used_by_the_form_and_filters(): void
    {
        $vehicle = Vehicle::factory()->create(['tracking_start_date' => '2024-05-01']);
        ExpenseCategory::factory()->create(['name' => 'Ativa', 'sort_order' => 1]);
        ExpenseCategory::factory()->inactive()->create(['name' => 'Antiga', 'sort_order' => 2]);

        $this->get('/expenses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expenses/Index')
                ->has('options.vehicles', 1)
                ->where('options.vehicles.0.value', $vehicle->id)
                ->where('options.vehicles.0.tracking_start_date', '2024-05-01')
                ->has('options.categories', 2)
                ->where('options.categories.1.active', false)
                ->has('options.payment_methods', 7)
                ->where('total_cents', 0));
    }

    public function test_expense_rows_carry_recurrence_and_tracking_information(): void
    {
        $vehicle = Vehicle::factory()->create(['tracking_start_date' => '2024-05-01']);
        Expense::factory()->create([
            'vehicle_id' => $vehicle->id,
            'occurred_on' => '2023-12-10',
            'recurring_expense_id' => 7,
        ]);

        $this->get('/expenses')->assertInertia(fn (Assert $page) => $page
            ->where('expenses.data.0.recurring_expense_id', 7)
            ->where('expenses.data.0.occurred_on', '2023-12-10')
            ->where('expenses.data.0.vehicle.name', $vehicle->name)
            ->has('expenses.data.0.vehicle.tracking_start_date')
            ->has('expenses.data.0.category.name'));
    }

    public function test_combined_filters_narrow_the_list_and_the_total(): void
    {
        $vehicle = Vehicle::factory()->create();
        $other = Vehicle::factory()->create();
        $category = ExpenseCategory::factory()->create();

        Expense::factory()->create(['vehicle_id' => $vehicle->id, 'expense_category_id' => $category->id, 'occurred_on' => '2024-03-01', 'amount_cents' => 1_000, 'description' => 'Pneu dianteiro']);
        Expense::factory()->create(['vehicle_id' => $vehicle->id, 'expense_category_id' => $category->id, 'occurred_on' => '2025-03-01', 'amount_cents' => 2_000, 'description' => 'Pneu traseiro']);
        Expense::factory()->create(['vehicle_id' => $other->id, 'expense_category_id' => $category->id, 'occurred_on' => '2024-03-02', 'amount_cents' => 4_000, 'description' => 'Pneu estepe']);

        $this->get("/expenses?vehicle_id={$vehicle->id}&expense_category_id={$category->id}&from=2024-01-01&to=2024-12-31&search=Pneu")
            ->assertInertia(fn (Assert $page) => $page
                ->where('expenses.total', 1)
                ->where('total_cents', 1_000)
                ->where('filters.vehicle_id', (string) $vehicle->id)
                ->where('filters.from', '2024-01-01'));
    }

    public function test_receipt_can_be_viewed_inline_or_downloaded(): void
    {
        $this->post('/expenses', [
            'vehicle_id' => Vehicle::factory()->create()->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'description' => 'Nota',
            'amount_cents' => 1_000,
            'occurred_on' => '2022-08-01',
            'receipt' => UploadedFile::fake()->create('nota.pdf', 50, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $expense = Expense::first();

        $inline = $this->get("/expenses/{$expense->id}/receipt")->assertOk();
        $this->assertStringStartsWith('inline', (string) $inline->headers->get('content-disposition'));

        $download = $this->get("/expenses/{$expense->id}/receipt?download=1")->assertOk();
        $this->assertStringStartsWith('attachment', (string) $download->headers->get('content-disposition'));
    }

    public function test_expense_from_a_previous_year_can_be_registered_and_found(): void
    {
        $this->post('/expenses', [
            'vehicle_id' => Vehicle::factory()->create()->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'description' => 'Revisão de 2021',
            'amount_cents' => 45_000,
            'occurred_on' => '2021-11-05',
        ])->assertRedirect('/expenses');

        $this->get('/expenses?from=2021-01-01&to=2021-12-31')
            ->assertInertia(fn (Assert $page) => $page->where('expenses.total', 1)->where('total_cents', 45_000));
    }
}
