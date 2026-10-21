<?php

namespace App\Models;

use App\Enums\RecurrenceFrequency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringExpense extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_id',
        'expense_category_id',
        'description',
        'expected_amount_cents',
        'frequency',
        'start_date',
        'end_date',
        'due_day',
        'active',
        'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'active' => true,
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'monthly_estimate_cents',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_amount_cents' => 'integer',
            'frequency' => RecurrenceFrequency::class,
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'due_day' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * @return HasMany<RecurringExpenseOccurrence, $this>
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(RecurringExpenseOccurrence::class);
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Estimativa mensal proporcional: valor previsto ÷ meses entre ocorrências
     * (ex.: seguro anual de R$ 1.200,00 ≈ R$ 100,00 por mês). Não é valor pago.
     */
    public function getMonthlyEstimateCentsAttribute(): int
    {
        return (int) round($this->expected_amount_cents / $this->frequency->months());
    }
}
