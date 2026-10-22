<?php

namespace App\Models;

use App\Enums\OccurrenceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringExpenseOccurrence extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'recurring_expense_id',
        'competence_date',
        'due_date',
        'expected_amount_cents',
        'status',
        'paid_at',
        'paid_amount_cents',
        'expense_id',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'competence_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'expected_amount_cents' => 'integer',
            'status' => OccurrenceStatus::class,
            'paid_at' => 'date:Y-m-d',
            'paid_amount_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<RecurringExpense, $this>
     */
    public function recurringExpense(): BelongsTo
    {
        return $this->belongsTo(RecurringExpense::class);
    }

    /**
     * @return BelongsTo<Expense, $this>
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function scopeForecast(Builder $query): Builder
    {
        return $query->where('status', OccurrenceStatus::Forecast);
    }

    public function isForecast(): bool
    {
        return $this->status === OccurrenceStatus::Forecast;
    }
}
