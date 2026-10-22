<?php

namespace Tests\Feature;

use App\Domain\Expenses\ExpenseReceiptStorage;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private Vehicle $vehicle;

    private ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake(ExpenseReceiptStorage::DISK);

        $this->vehicle = Vehicle::factory()->create();
        $this->category = ExpenseCategory::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $this->vehicle->id,
            'expense_category_id' => $this->category->id,
            'description' => 'Troca de óleo',
            'amount_cents' => 25_990,
            'occurred_on' => '2025-03-10',
        ], $overrides);
    }

    public function test_expense_can_be_created_and_keeps_amount_in_cents(): void
    {
        $this->post('/expenses', $this->payload(['vendor' => 'Oficina X', 'payment_method' => 'pix', 'odometer' => 41_000]))
            ->assertRedirect('/expenses');

        $expense = Expense::first();
        $this->assertSame(25_990, $expense->amount_cents);
        $this->assertSame('pix', $expense->payment_method->value);
        $this->assertSame(41_000, $expense->odometer);
    }

    public function test_retroactive_expenses_from_2023_2024_and_2025_keep_their_real_date(): void
    {
        foreach (['2023-06-15', '2024-02-29', '2025-12-31'] as $date) {
            $this->post('/expenses', $this->payload(['occurred_on' => $date]))->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['2023-06-15', '2024-02-29', '2025-12-31'],
            Expense::orderBy('occurred_on')->get()->map(fn ($e) => $e->occurred_on->toDateString())->all(),
        );

        $this->get('/expenses?from=2024-01-01&to=2024-12-31')
            ->assertInertia(fn (Assert $page) => $page
                ->has('expenses.data', 1)
                ->where('expenses.data.0.occurred_on', '2024-02-29')
                ->where('total_cents', 25_990));
    }

    public function test_validation_rules(): void
    {
        $this->post('/expenses', [])->assertSessionHasErrors([
            'vehicle_id', 'expense_category_id', 'description', 'amount_cents', 'occurred_on',
        ]);

        $this->post('/expenses', $this->payload(['amount_cents' => 0]))
            ->assertSessionHasErrors(['amount_cents' => 'O valor deve ser maior que zero.']);
        $this->post('/expenses', $this->payload(['occurred_on' => 'ontem']))->assertSessionHasErrors('occurred_on');
        $this->post('/expenses', $this->payload(['odometer' => -1]))->assertSessionHasErrors('odometer');
        $this->post('/expenses', $this->payload(['payment_method' => 'cheque']))->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Expense::count());
    }

    public function test_inactive_category_is_rejected_on_create_but_kept_on_old_expenses(): void
    {
        $inactive = ExpenseCategory::factory()->inactive()->create();

        $this->post('/expenses', $this->payload(['expense_category_id' => $inactive->id]))
            ->assertSessionHasErrors(['expense_category_id' => 'A categoria selecionada está inativa.']);

        $old = Expense::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'expense_category_id' => $inactive->id,
        ]);

        $this->put("/expenses/{$old->id}", $this->payload(['expense_category_id' => $inactive->id, 'description' => 'Ajustada']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Ajustada', $old->fresh()->description);

        $this->put("/expenses/{$old->id}", $this->payload(['expense_category_id' => $this->category->id]))
            ->assertSessionHasNoErrors();
    }

    public function test_archived_vehicle_cannot_receive_new_expenses(): void
    {
        $this->vehicle->delete();

        $this->post('/expenses', $this->payload())->assertSessionHasErrors('vehicle_id');
    }

    public function test_vehicle_with_expenses_cannot_be_force_deleted(): void
    {
        Expense::factory()->create(['vehicle_id' => $this->vehicle->id]);

        $this->expectException(QueryException::class);

        $this->vehicle->forceDelete();
    }

    public function test_category_with_expenses_cannot_be_force_deleted(): void
    {
        Expense::factory()->create(['expense_category_id' => $this->category->id]);

        $this->expectException(QueryException::class);

        $this->category->delete();
    }

    public function test_receipt_is_stored_privately_served_by_route_and_replaced(): void
    {
        $this->post('/expenses', $this->payload(['receipt' => UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf')]))
            ->assertSessionHasNoErrors();

        $expense = Expense::first();
        $firstPath = $expense->getRawOriginal('receipt_path');
        Storage::disk(ExpenseReceiptStorage::DISK)->assertExists($firstPath);
        $this->assertStringStartsWith("expenses/{$expense->id}/", $firstPath);

        $this->get("/expenses/{$expense->id}/receipt")->assertOk();
        $this->get('/expenses')->assertInertia(fn (Assert $page) => $page
            ->where('expenses.data.0.has_receipt', true)
            ->missing('expenses.data.0.receipt_path'));

        $this->put("/expenses/{$expense->id}", $this->payload(['receipt' => UploadedFile::fake()->image('foto.png')]));

        $secondPath = $expense->fresh()->getRawOriginal('receipt_path');
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk(ExpenseReceiptStorage::DISK)->assertMissing($firstPath);
        Storage::disk(ExpenseReceiptStorage::DISK)->assertExists($secondPath);
    }

    public function test_receipt_rejects_invalid_type_and_size(): void
    {
        $this->post('/expenses', $this->payload(['receipt' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]))
            ->assertSessionHasErrors('receipt');
        $this->post('/expenses', $this->payload(['receipt' => UploadedFile::fake()->create('grande.pdf', 6_000, 'application/pdf')]))
            ->assertSessionHasErrors('receipt');
    }

    public function test_receipt_can_be_removed_and_missing_receipt_returns_404(): void
    {
        $this->post('/expenses', $this->payload(['receipt' => UploadedFile::fake()->image('a.jpg')]));
        $expense = Expense::first();
        $path = $expense->getRawOriginal('receipt_path');

        $this->put("/expenses/{$expense->id}", $this->payload(['remove_receipt' => true]));

        Storage::disk(ExpenseReceiptStorage::DISK)->assertMissing($path);
        $this->get("/expenses/{$expense->id}/receipt")->assertNotFound();
    }

    public function test_deleting_an_expense_removes_its_receipt_file(): void
    {
        $this->post('/expenses', $this->payload(['receipt' => UploadedFile::fake()->image('a.jpg')]));
        $expense = Expense::first();
        $path = $expense->getRawOriginal('receipt_path');

        $this->delete("/expenses/{$expense->id}")->assertRedirect('/expenses');

        $this->assertModelMissing($expense);
        Storage::disk(ExpenseReceiptStorage::DISK)->assertMissing($path);
    }

    public function test_index_filters_by_vehicle_category_and_search_and_paginates_by_20(): void
    {
        $other = Vehicle::factory()->create();
        $fuel = ExpenseCategory::factory()->create();

        Expense::factory()->count(22)->create(['vehicle_id' => $this->vehicle->id, 'expense_category_id' => $this->category->id]);
        Expense::factory()->create(['vehicle_id' => $other->id, 'expense_category_id' => $fuel->id, 'description' => 'Pastilha especial', 'amount_cents' => 5_000]);

        $this->get('/expenses')->assertInertia(fn (Assert $page) => $page
            ->has('expenses.data', 20)
            ->where('expenses.total', 23));

        $this->get("/expenses?vehicle_id={$other->id}")->assertInertia(fn (Assert $page) => $page->where('expenses.total', 1));
        $this->get("/expenses?expense_category_id={$fuel->id}")->assertInertia(fn (Assert $page) => $page->where('expenses.total', 1));
        $this->get('/expenses?search=especial')->assertInertia(fn (Assert $page) => $page
            ->where('expenses.total', 1)
            ->where('total_cents', 5_000));
    }

    public function test_totals_use_occurred_on_and_not_created_at(): void
    {
        $expense = Expense::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'occurred_on' => '2023-01-10',
            'amount_cents' => 10_000,
        ]);

        $this->assertSame(now()->year, $expense->created_at->year);

        $this->get('/expenses?from=2023-01-01&to=2023-12-31')
            ->assertInertia(fn (Assert $page) => $page->where('total_cents', 10_000));
        $this->get('/expenses?from='.now()->startOfYear()->toDateString())
            ->assertInertia(fn (Assert $page) => $page->where('total_cents', 0));
    }
}
