<?php

namespace App\Models;

use App\Enums\FuelType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'brand',
        'model',
        'version',
        'manufacture_year',
        'model_year',
        'category',
        'plate',
        'color',
        'fuel',
        'odometer',
        'acquisition_date',
        'acquisition_odometer',
        'purchase_price_cents',
        'sale_date',
        'sale_price_cents',
        'photo_path',
        'notes',
        'status',
        'tracking_start_date',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'full_name',
        'formatted_plate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => VehicleCategory::class,
            'status' => VehicleStatus::class,
            'fuel' => FuelType::class,
            'manufacture_year' => 'integer',
            'model_year' => 'integer',
            'odometer' => 'integer',
            'acquisition_odometer' => 'integer',
            'purchase_price_cents' => 'integer',
            'sale_price_cents' => 'integer',
            'acquisition_date' => 'date',
            'sale_date' => 'date',
            'tracking_start_date' => 'date',
        ];
    }

    /**
     * Normaliza a placa em maiúsculas e sem separadores antes de persistir.
     */
    protected function plate(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null || $value === ''
                ? null
                : strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value)),
        );
    }

    /**
     * Nome completo para exibição: marca, modelo e versão.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(implode(' ', array_filter([
                $this->brand,
                $this->model,
                $this->version,
            ]))),
        );
    }

    /**
     * Placa formatada para exibição (ex.: "ABC-1234" ou "ABC1D23" no padrão Mercosul).
     */
    protected function formattedPlate(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $plate = (string) $this->plate;

                if ($plate === '') {
                    return null;
                }

                if (preg_match('/^[A-Z]{3}[0-9]{4}$/', $plate) === 1) {
                    return substr($plate, 0, 3).'-'.substr($plate, 3);
                }

                return $plate;
            },
        );
    }

    /**
     * Veículos com status ativo.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VehicleStatus::Active);
    }

    /**
     * Veículos vendidos.
     */
    public function scopeSold(Builder $query): Builder
    {
        return $query->where('status', VehicleStatus::Sold);
    }

    /**
     * Veículos arquivados (soft deleted).
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->onlyTrashed();
    }

    public function isArchived(): bool
    {
        return $this->trashed();
    }

    public function isSold(): bool
    {
        return $this->status === VehicleStatus::Sold;
    }
}
