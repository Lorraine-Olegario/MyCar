<?php

namespace Tests\Feature;

use App\Domain\Recurring\RecurringExpenseService;
use App\Enums\OccurrenceStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RecurringExpense;
use App\Models\RecurringExpenseOccurrence;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RecurringExpensePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-01-15');
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
            'vehicle_id' => Vehicle::factory()->create()->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'description' => 'Seguro anual',
            'expected_amount_cents' => 240_000,
            'frequency' => 'annual',
            'start_date' => '2026-03-01',
            'due_day' => 5,
        ], $overrides);
    }

    private function create(array $overrides = []): RecurringExpense
    {
        return app(RecurringExpenseService::class)->create($this->payload($overrides));
    }

    public function test_index_exposes_models_timeline_summary_and_upcoming(): void
    {
        $recurring = $this->create(['frequency' => 'monthly', 'start_date' => '2026-01-01', 'due_day' => 10, 'expected_amount_cents' => 10_000]);

        $this->get('/recurring')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Recurring/Index')
                ->has('recurring', 1)
                ->where('recurring.0.next_due_date', fn ($date) => str_starts_with($date, '2026-01-10'))
                ->where('recurring.0.monthly_estimate_cents', 10_000)
                ->has("occurrences.{$recurring->id}", 13)
                ->where('summary.estimated_cents', 10_000)
                ->where('summary.paid_cents', 0)
                ->where('summary.forecast_cents', 10_000)
                ->has('upcoming', 4)
                ->where('upcoming.0.due_date', '2026-01-10')
                ->where('upcoming.0.is_overdue', true)
                ->has('options.frequencies', 4));
    }

    public function test_recurring_can_be_created_and_generates_forecasts(): void
    {
        $this->post('/recurring', $this->payload(['frequency' => 'quarterly', 'start_date' => '2026-01-01']))
            ->assertRedirect('/recurring')
            ->assertSessionHasNoErrors();

        $recurring = RecurringExpense::first();
        $this->assertSame(5, $recurring->occurrences()->count());
        $this->assertSame(0, Expense::count());
    }

    public function test_validation_messages(): void
    {
        $this->post('/recurring', [])->assertSessionHasErrors(['vehicle_id', 'expense_category_id', 'description', 'expected_amount_cents', 'frequency', 'start_date', 'due_day']);
        $this->post('/recurring', $this->payload(['due_day' => 32]))->assertSessionHasErrors(['due_day' => 'O dia de vencimento deve estar entre 1 e 31.']);
        $this->post('/recurring', $this->payload(['end_date' => '2025-01-01']))->assertSessionHasErrors('end_date');
        $this->post('/recurring', $this->payload(['frequency' => 'weekly']))->assertSessionHasErrors('frequency');
        $this->post('/recurring', $this->payload(['expected_amount_cents' => 0]))->assertSessionHasErrors('expected_amount_cents');
        $this->post('/recurring', $this->payload(['expense_category_id' => ExpenseCategory::factory()->inactive()->create()->id]))
            ->assertSessionHasErrors(['expense_category_id' => 'A categoria selecionada está inativa.']);
    }

    public function test_update_recalculates_only_future_forecasts(): void
    {
        $recurring = $this->create(['frequency' => 'monthly', 'start_date' => '2026-01-01', 'expected_amount_cents' => 10_000]);
        $paid = $recurring->occurrences()->orderBy('competence_date')->first();
        app(RecurringExpenseService::class)->confirmPayment($paid, '2026-01-10', 10_000);

        $this->put("/recurring/{$recurring->id}", $this->payload([
            'vehicle_id' => $recurring->vehicle_id,
            'expense_category_id' => $recurring->expense_category_id,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'expected_amount_cents' => 30_000,
            'due_day' => 10,
        ]))->assertRedirect('/recurring');

        $this->assertSame(10_000, $paid->fresh()->expected_amount_cents);
        $this->assertSame(30_000, $recurring->occurrences()->whereDate('competence_date', '2026-02-01')->first()->expected_amount_cents);
    }

    public function test_confirming_payment_creates_exactly_one_expense(): void
    {
        $recurring = $this->create(['frequency' => 'monthly', 'start_date' => '2026-01-01', 'expected_amount_cents' => 10_000]);
        $occurrence = $recurring->occurrences()->orderBy('competence_date')->first();

        $this->post("/recurring/occurrences/{$occurrence->id}/pay", ['paid_on' => '2026-01-12', 'paid_amount_cents' => 10_500])
            ->assertRedirect('/recurring')
            ->assertSessionHas('success');

        $this->post("/recurring/occurrences/{$occurrence->id}/pay", ['paid_on' => '2026-01-12', 'paid_amount_cents' => 10_500])
            ->assertRedirect('/recurring')
            ->assertSessionHas('error');

        $this->assertSame(1, Expense::count());
        $this->assertSame(OccurrenceStatus::Paid, $occurrence->fresh()->status);
        $this->assertSame(10_500, (int) Expense::sum('amount_cents'));
    }

    public function test_payment_requires_valid_date_and_amount(): void
    {
        $occurrence = $this->create()->occurrences()->first();

        $this->post("/recurring/occurrences/{$occurrence->id}/pay", [])->assertSessionHasErrors(['paid_on', 'paid_amount_cents']);
        $this->post("/recurring/occurrences/{$occurrence->id}/pay", ['paid_on' => '2026-01-12', 'paid_amount_cents' => 0])
            ->assertSessionHasErrors(['paid_amount_cents' => 'O valor pago deve ser maior que zero.']);
        $this->assertSame(0, Expense::count());
    }

    public function test_forecast_can_be_canceled_and_paid_one_cannot(): void
    {
        $recurring = $this->create(['frequency' => 'monthly', 'start_date' => '2026-01-01']);
        [$first, $second] = $recurring->occurrences()->orderBy('competence_date')->take(2)->get();

        $this->patch("/recurring/occurrences/{$first->id}/cancel")->assertSessionHas('success');
        $this->assertSame(OccurrenceStatus::Canceled, $first->fresh()->status);

        app(RecurringExpenseService::class)->confirmPayment($second, '2026-02-10', 1_000);
        $this->patch("/recurring/occurrences/{$second->id}/cancel")->assertSessionHas('error');
        $this->assertSame(OccurrenceStatus::Paid, $second->fresh()->status);
    }

    public function test_upcoming_list_excludes_paid_canceled_far_future_and_inactive(): void
    {
        $service = app(RecurringExpenseService::class);
        $recurring = $this->create(['frequency' => 'monthly', 'start_date' => '2026-01-01', 'due_day' => 20]);
        [$jan, $feb] = $recurring->occurrences()->orderBy('competence_date')->take(2)->get();

        $service->confirmPayment($jan, '2026-01-20', 1_000);
        $service->cancel($feb);

        $items = $service->upcoming(withinDays: 30);
        $this->assertSame([], $items);

        $items = $service->upcoming(withinDays: 120, limit: 2);
        $this->assertCount(2, $items);
        $this->assertSame('2026-03-20', $items[0]['due_date']);
        $this->assertFalse($items[0]['is_overdue']);
        $this->assertSame(64, $items[0]['days_until_due']);

        $service->update($recurring, ['active' => false]);
        $this->assertSame([], $service->upcoming());
        $this->assertSame(1, RecurringExpenseOccurrence::where('status', OccurrenceStatus::Paid)->count());
    }
}
