<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_id',
        'expense_category_id',
        'description',
        'amount_cents',
        'occurred_on',
        'odometer',
        'vendor',
        'payment_method',
        'receipt_path',
        'notes',
        'recurring_expense_id',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'has_receipt',
    ];

    /**
     * O caminho no disco privado não vai para a interface; ela usa a rota do comprovante.
     *
     * @var list<string>
     */
    protected $hidden = [
        'receipt_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'occurred_on' => 'date:Y-m-d',
            'odometer' => 'integer',
            'payment_method' => PaymentMethod::class,
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

    public function getHasReceiptAttribute(): bool
    {
        return filled($this->receipt_path);
    }

    /**
     * Despesas dentro de um período, sempre pela data efetiva (`occurred_on`).
     */
    public function scopeOccurredBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $query) => $query->whereDate('occurred_on', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('occurred_on', '<=', $to));
    }
}
