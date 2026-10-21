<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Visão geral: veículo ativo mais recente e atalhos.
     */
    public function __invoke(): Response
    {
        $vehicle = Vehicle::query()
            ->where('status', VehicleStatus::Active)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return Inertia::render('Dashboard', [
            'vehicle' => $vehicle,
            'fuelLabel' => $vehicle?->fuel?->label(),
            'monthLabel' => mb_strtoupper(Carbon::now()->translatedFormat('F \d\e Y')),
            'indicators' => [
                'total_spent_cents' => null,
                'cost_per_km_cents' => null,
                'average_consumption' => null,
                'next_maintenance' => null,
            ],
        ]);
    }
}
