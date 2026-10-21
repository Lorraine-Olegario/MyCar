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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'archived',
        'photo_url',
        'sale_result_cents',
        'ownership_label',
    ];

    /**
     * O caminho no disco privado não vai para a interface; ela usa `photo_url`.
     *
     * @var list<string>
     */
    protected $hidden = [
        'photo_path',
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
            'fipe_value_cents' => 'integer',
            'fipe_consulted_at' => 'datetime',
        ];
    }

    /**
     * Histórico de cotações FIPE (mais recente primeiro).
     *
     * @return HasMany<FipeQuote, $this>
     */
    public function fipeQuotes(): HasMany
    {
        return $this->hasMany(FipeQuote::class)->orderByDesc('consulted_at')->orderByDesc('id');
    }

    /**
     * Cotação mais recente persistida; os campos `fipe_*` do veículo sempre refletem esta.
     *
     * @return HasOne<FipeQuote, $this>
     */
    public function latestFipeQuote(): HasOne
    {
        return $this->hasOne(FipeQuote::class)->latestOfMany(['consulted_at', 'id']);
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
     * Indica se o veículo está arquivado (soft delete).
     */
    protected function archived(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->trashed(),
        );
    }

    /**
     * URL da rota controlada que serve a foto do veículo; nula quando não há foto.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => filled($this->photo_path)
                ? route('vehicles.photo', $this)
                : null,
        );
    }

    /**
     * Resultado da venda (venda - compra) em centavos; nulo quando falta o valor
     * de compra ou de venda. As despesas operacionais entram na Fase 4.
     */
    protected function saleResultCents(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->purchase_price_cents === null || $this->sale_price_cents === null
                ? null
                : $this->sale_price_cents - $this->purchase_price_cents,
        );
    }

    /**
     * Período de propriedade em texto (ex.: "2 anos e 3 meses"): da aquisição até a
     * venda ou, para veículos ativos, até hoje. Nulo sem data de aquisição.
     */
    protected function ownershipLabel(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if ($this->acquisition_date === null) {
                    return null;
                }

                $end = $this->sale_date ?? now();
                $months = max(0, (int) $this->acquisition_date->diffInMonths($end));

                if ($months < 1) {
                    return 'menos de 1 mês';
                }

                $years = intdiv($months, 12);
                $rest = $months % 12;

                $parts = array_filter([
                    $years > 0 ? $years.($years === 1 ? ' ano' : ' anos') : null,
                    $rest > 0 ? $rest.($rest === 1 ? ' mês' : ' meses') : null,
                ]);

                return implode(' e ', $parts);
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
