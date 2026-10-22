<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma consulta à FIPE. Cada consulta bem-sucedida vira um registro: o histórico nunca é
 * sobrescrito (sem índice único por veículo/mês).
 */
class FipeQuote extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_id',
        'fipe_code',
        'value_cents',
        'reference_month',
        'brand',
        'model',
        'model_year',
        'fuel',
        'consulted_at',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_cents' => 'integer',
            'model_year' => 'integer',
            'consulted_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }
}
