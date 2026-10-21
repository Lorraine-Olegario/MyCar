<?php

namespace App\Domain\Fipe;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;

/**
 * Valor FIPE de referência da frota ativa.
 *
 * Só entram veículos ativos (nem vendidos, nem arquivados) e só cotações atuais: uma
 * cotação antiga (mais de `services.fipe.stale_days` dias) é contada como "desatualizada"
 * e NÃO entra na soma. O resultado é referência de mercado, nunca somada a gastos.
 */
class FipeFleetSummary
{
    /**
     * @return array{total_cents: ?int, vehicles: int, with_quote: int, stale: int, missing: int}
     */
    public function summarize(?int $vehicleId = null, ?Carbon $now = null): array
    {
        $now ??= now();
        $limit = $now->copy()->subDays((int) config('services.fipe.stale_days'));

        $vehicles = Vehicle::query()
            ->where('status', VehicleStatus::Active)
            ->when($vehicleId, fn ($query, $id) => $query->whereKey($id))
            ->get(['id', 'fipe_value_cents', 'fipe_consulted_at']);

        $fresh = $vehicles->filter(fn (Vehicle $vehicle) => $vehicle->fipe_value_cents !== null
            && $vehicle->fipe_consulted_at !== null
            && $vehicle->fipe_consulted_at->gte($limit));
        $stale = $vehicles->filter(fn (Vehicle $vehicle) => $vehicle->fipe_value_cents !== null
            && $vehicle->fipe_consulted_at !== null
            && $vehicle->fipe_consulted_at->lt($limit));

        return [
            'total_cents' => $fresh->isEmpty() ? null : (int) $fresh->sum('fipe_value_cents'),
            'vehicles' => $vehicles->count(),
            'with_quote' => $fresh->count(),
            'stale' => $stale->count(),
            'missing' => $vehicles->count() - $fresh->count() - $stale->count(),
        ];
    }
}
