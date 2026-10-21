<?php

namespace App\Models;

use App\Enums\FuelType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fueling extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_id',
        'fueled_on',
        'odometer',
        'liters',
        'price_per_liter',
        'total_cents',
        'fuel_type',
        'station',
        'full_tank',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fueled_on' => 'date:Y-m-d',
            'odometer' => 'integer',
            'liters' => 'decimal:3',
            'price_per_liter' => 'decimal:3',
            'total_cents' => 'integer',
            'fuel_type' => FuelType::class,
            'full_tank' => 'boolean',
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
     * Total em centavos a partir de litros e preço por litro (arredondado ao centavo).
     */
    public static function totalCentsFor(float|string $liters, float|string $pricePerLiter): int
    {
        return (int) round((float) $liters * (float) $pricePerLiter * 100);
    }
}
