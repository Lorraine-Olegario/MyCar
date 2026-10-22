<?php

namespace Tests\Feature;

use App\Domain\Recurring\RecurringExpenseService;
use App\Enums\OccurrenceStatus;
use App\Enums\RecurrenceFrequency;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RecurringExpense;
use App\Models\RecurringExpenseOccurrence;
use App\Models\Vehicle;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringExpenseTest extends TestCase
{
    use RefreshDatabase;

    private RecurringExpenseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-01-15');
        $this->service = app(RecurringExpenseService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function recurring(RecurrenceFrequency $frequency, array $overrides = []): RecurringExpense
    {
        return $this->service->create(RecurringExpense::factory()->frequency($frequency)->make(array_merge([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'expense_category_id' => ExpenseCategory::factory()->create()->id,
            'start_date' => '2026-01-01',
            'due_day' => 10,
            'expected_amount_cents' => 120_000,
        ], $overrides))->getAttributes());
    }

    /**
     * @return list<string>
     */
    private function competences(RecurringExpense $recurring): array
    {
        return $recurring->occurrences()->orderBy('competence_date')->get()
            ->map(fn ($occurrence) => $occurrence->competence_date->format('Y-m'))->all();
    }

    public function test_monthly_generation_covers_the_next_twelve_months(): void
    {
        $recurring = $this->recurring(RecurrenceFrequency::Monthly);

        $this->assertCount(13, $recurring->occurrences);
        $this->assertSame('2026-01', $this->competences($recurring)[0]);
        $this->assertSame('2027-01', $this->competences($recurring)[12]);
        $this->assertSame('2026-02-10', $recurring->occurrences()->orderBy('competence_date')->skip(1)->first()->due_date->toDateString());
    }

    public function test_quarterly_semiannual_and_annual_generation(): void
    {
        $this->assertSame(
            ['2026-01', '2026-04', '2026-07', '2026-10', '2027-01'],
            $this->competences($this->recurring(RecurrenceFrequency::Quarterly)),
        );
        $this->assertSame(
            ['2026-01', '2026-07', '2027-01'],
            $this->competences($this->recurring(RecurrenceFrequency::Semiannual)),
        );
        $this->assertSame(
            ['2026-01', '2027-01'],
            $this->competences($this->recurring(RecurrenceFrequency::Annual)),
        );
    }

    public function test_anchor_month_defines_the_cycle_and_past_months_are_not_generated(): void
    {
        $recurring = $this->recurring(RecurrenceFrequency::Quarterly, ['start_date' => '2025-02-20']);

        $this->assertSame(['2026-02', '2026-05', '2026-08', '2026-11'], $this->competences($recurring));
    }

    public function test_future_start_and_end_date_are_respected(): void
    {
        $future = $this->recurring(RecurrenceFrequency::Monthly, ['start_date' => '2026-06-05']);
        $this->assertSame('2026-06', $this->competences($future)[0]);

        $ended = $this->recurring(RecurrenceFrequency::Monthly, ['end_date' => '2026-03-31']);
        $this->assertSame(['2026-01', '2026-02', '2026-03'], $this->competences($ended));
    }

    public function test_due_day_is_clamped_in_short_months(): void
    {
        $recurring = $this->recurring(RecurrenceFrequency::Monthly, ['due_day' => 31]);

        $dues = $recurring->occurrences()->orderBy('competence_date')->get()
            ->mapWithKeys(fn ($o) => [$o->competence_date->format('Y-m') => $o->due_date->toDateString()]);

        $this->assertSame('2026-01-31', $dues['2026-01']);
        $this->assertSame('2026-02-28', $dues['2026-02']);
        $this->assertSame('2026-04-30', $dues['2026-04']);
    }

    public function test_leap_year_february_uses_day_29(): void
    {
        Carbon::setTestNow('2028-01-10');
        $recurring = $this->recurring(RecurrenceFrequency::Monthly, ['due_day' => 30, 'start_date' => '2028-01-01']);

        $february = $recurring->occurrences()->whereDate('competence_date', '2028-02-01')->first();
        $this->assertSame('2028-02-29', $february->due_date->toDateString());
    }

    public function test_generation_is_idempotent_via_service_and_command(): void
    {
        $recurring = $this->recurring(RecurrenceFrequency::Monthly);

        $this->service->sync($recurring);
        $this->artisan('recurring:generate')->assertSuccessful();
        $this->artisan('recurring:generate', ['--months' => 12])->assertSuccessful();

        $this->assertSame(13, RecurringExpenseOccurrence::count());
    }

    public function test_command_extends_the_horizon_when_months_grows(): void
    {
        $recurring = $this->recurring(RecurrenceFrequency::Monthly);

        $this->artisan('recurring:generate', ['--months' => 24])->assertSuccessful();
        $this->assertSame(25, $recurring->occurrences()->count());

        $this->artisan('recurring:generate', ['--months' => 6])->assertSuccessful();
        $this->assertSame(25, $recurring->occurrences()->count());
    }

    public function test_generate_command_is_scheduled_daily(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'recurring:generate'));

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
    }

    public function test_inactive_recurring_generates_nothing(): void
    {
        $recurring = $this->recurring(RecurrenceFrequency::Monthly, ['active' => false]);

        $this->assertSame(0, $recurring->occurrences()->count());
    }

    public function test_confirming_payment_creates_one_linked_expense(): void
    {
        $recurring = $this->recurring(RecurrenceFrequency::Monthly, ['expected_amount_cents' => 50_000]);
        $occurrence = $recurring->occurrences()->orderBy('competence_date')->first();

        $expense = $this->service->confirmPayment($occurrence, '2026-01-12', 52_300);

        $this->assertSame(1, Expense::count());
        $this->assertSame(52_300, $expense->amount_cents);
        $this->assertSame('2026-01-12', $expense->occurred_on->toDateString());
        $this->assertSame($recurring->vehicle_id, $expense->vehicle_id);
        $this->assertSame($recurring->expense_category_id, $expense->expense_category_id);
        $this->assertSame($recurring->id, $expense->recurring_expense_id);

        $occurrence->refresh();
        $this->assertSame(OccurrenceStatus::Paid, $occurrence->status);
        $this->assertSame(52_300, $occurrence->paid_amount_cents);
        $this->assertSame($expense->id, $occurrence->expense_id);
    }

    public function test_double_confirmation_is_blocked(): void
    {
        $occurrence = $this->recurring(RecurrenceFrequency::Monthly)->occurrences()->first();
        $this->service->confirmPayment($occurrence, '2026-01-12', 10_000);

        $this->expectExceptionMessage('já teve o pagamento confirmado');

        try {
            $this->service->confirmPayment($occurrence->fresh(), '2026-01-13', 10_000);
        } finally {
            $this->assertSame(1, Expense::count());
        }
    }

    public function test_forecast_can_be_canceled_but_not_paid_afterwards(): void
    {
        $occurrence = $this->recurring(RecurrenceFrequency::Monthly)->occurrences()->first();

        $this->service->cancel($occurrence);
        $this->assertSame(OccurrenceStatus::Canceled, $occurrence->fresh()->status);

        $this->expectExceptionMessage('cancelada');
        $this->service->confirmPayment($occurrence->fresh(), '2026-01-12', 10_000);
    }

    public function test_paid_occurrence_cannot_be_canceled(): void
    {
        $occurrence = $this->recurring(RecurrenceFrequency::Monthly)->occurrences()->first();
        $this->service->confirmPayment($occurrence, '2026-01-12', 10_000);

        $this->expectExceptionMessage('Somente previsões em aberto');
        $this->service->cancel($occurrence->fresh());
    }

    public function test_payment_requires_a_positive_amount(): void
    {
        $occurrence = $this->recurring(RecurrenceFrequency::Monthly)->occurrences()->first();

        $this->expectExceptionMessage('maior que zero');
        $this->service->confirmPayment($occurrence, '2026-01-12', 0);
    }

    public function test_forecasts_never_count_as_realized_expenses(): void
    {
        $recurring = $this->recurring(RecurrenceFrequency::Monthly, ['expected_amount_cents' => 80_000]);

        $this->assertSame(0, Expense::count());
        $this->assertSame(0, (int) Expense::sum('amount_cents'));
        $this->assertSame(0, $this->service->monthSummary(Carbon::parse('2026-01-01'))['paid_cents']);

        $this->service->confirmPayment($recurring->occurrences()->orderBy('competence_date')->first(), '2026-01-10', 79_000);

        $this->assertSame(79_000, (int) Expense::sum('amount_cents'));
    }

    public function test_editing_the_model_changes_only_future_forecasts(): void
    {
        $recurring = $this->recurring(RecurrenceFrequency::Monthly, ['expected_amount_cents' => 10_000]);
        $january = $recurring->occurrences()->orderBy('competence_date')->first();
        $february = $recurring->occurrences()->orderBy('competence_date')->skip(1)->first();

        $this->service->confirmPayment($january, '2026-01-10', 10_000);
        $this->service->cancel($february);

        $this->service->update($recurring, ['expected_amount_cents' => 20_000, 'due_day' => 20]);

        $this->assertSame(10_000, $january->fresh()->expected_amount_cents);
        $this->assertSame('2026-01-10', $january->fresh()->due_date->toDateString());
        $this->assertSame(10_000, $february->fresh()->expected_amount_cents);
        $this->assertSame(OccurrenceStatus::Canceled, $february->fresh()->status);

        $march = $recurring->occurrences()->whereDate('competence_date', '2026-03-01')->first();
        $this->assertSame(20_000, $march->expected_amount_cents);
        $this->assertSame('2026-03-20', $march->due_date->toDateString());
    }

    public function test_changing_frequency_or_end_removes_only_future_forecasts_that_no_longer_fit(): void
    {
        $recurring = $this->recurring(RecurrenceFrequency::Monthly);
        $paid = $recurring->occurrences()->orderBy('competence_date')->first();
        $this->service->confirmPayment($paid, '2026-01-10', 1_000);

        $this->service->update($recurring, ['frequency' => RecurrenceFrequency::Quarterly]);

        $this->assertSame(['2026-01', '2026-04', '2026-07', '2026-10', '2027-01'], $this->competences($recurring));
        $this->assertSame(OccurrenceStatus::Paid, $paid->fresh()->status);

        $this->service->update($recurring, ['active' => false]);

        $this->assertSame(['2026-01'], $this->competences($recurring));
    }

    public function test_deleting_the_generated_expense_reopens_the_occurrence(): void
    {
        $occurrence = $this->recurring(RecurrenceFrequency::Monthly)->occurrences()->first();
        $expense = $this->service->confirmPayment($occurrence, '2026-01-10', 5_000);

        $expense->delete();

        $occurrence->refresh();
        $this->assertSame(OccurrenceStatus::Forecast, $occurrence->status);
        $this->assertNull($occurrence->expense_id);
        $this->assertNull($occurrence->paid_amount_cents);
    }

    public function test_annual_expense_shows_paid_and_monthly_estimate_separately(): void
    {
        $recurring = $this->recurring(RecurrenceFrequency::Annual, [
            'expected_amount_cents' => 120_000,
            'start_date' => '2026-03-01',
        ]);

        $this->assertSame(10_000, $recurring->monthly_estimate_cents);

        $march = $recurring->occurrences()->first();
        $this->service->confirmPayment($march, '2026-03-05', 118_000);

        $marchSummary = $this->service->monthSummary(Carbon::parse('2026-03-10'));
        $this->assertSame(10_000, $marchSummary['estimated_cents']);
        $this->assertSame(118_000, $marchSummary['paid_cents']);

        $juneSummary = $this->service->monthSummary(Carbon::parse('2026-06-10'));
        $this->assertSame(10_000, $juneSummary['estimated_cents']);
        $this->assertSame(0, $juneSummary['paid_cents']);
        $this->assertSame(0, $juneSummary['forecast_cents']);

        $this->service->sync($recurring, months: 24);

        $nextMarch = $this->service->monthSummary(Carbon::parse('2027-03-01'));
        $this->assertSame(120_000, $nextMarch['forecast_cents']);
        $this->assertSame(0, $nextMarch['paid_cents']);
    }

    public function test_month_summary_can_be_limited_to_a_vehicle(): void
    {
        $a = $this->recurring(RecurrenceFrequency::Monthly, ['expected_amount_cents' => 30_000]);
        $this->recurring(RecurrenceFrequency::Monthly, ['expected_amount_cents' => 70_000]);

        $this->assertSame(100_000, $this->service->monthSummary(Carbon::parse('2026-01-01'))['estimated_cents']);
        $this->assertSame(30_000, $this->service->monthSummary(Carbon::parse('2026-01-01'), $a->vehicle_id)['estimated_cents']);
    }
}
