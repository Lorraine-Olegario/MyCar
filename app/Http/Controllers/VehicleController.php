<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * Lista os veículos com busca e filtros, 12 por página.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->string('search')->trim(),
            'status' => (string) $request->string('status'),
            'category' => (string) $request->string('category'),
            'archived' => $request->boolean('archived'),
        ];

        $vehicles = Vehicle::query()
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = '%'.$filters['search'].'%';

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', $search)
                        ->orWhere('brand', 'like', $search)
                        ->orWhere('model', 'like', $search)
                        ->orWhere('plate', 'like', $search);
                });
            })
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['category'] !== '', fn ($query) => $query->where('category', $filters['category']))
            ->when($filters['archived'], fn ($query) => $query->onlyTrashed())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => $filters,
        ]);
    }

    /**
     * Exibe um veículo.
     */
    public function show(Vehicle $vehicle): Response
    {
        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Cadastra um veículo.
     */
    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return to_route('vehicles.show', $vehicle)
            ->with('success', 'Veículo cadastrado com sucesso.');
    }

    /**
     * Atualiza um veículo.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return to_route('vehicles.show', $vehicle)
            ->with('success', 'Veículo atualizado com sucesso.');
    }

    /**
     * Arquiva (soft delete) um veículo; nada é apagado fisicamente.
     */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return to_route('vehicles.index')
            ->with('success', 'Veículo arquivado com sucesso.');
    }

    /**
     * Restaura um veículo arquivado.
     */
    public function restore(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->restore();

        return to_route('vehicles.show', $vehicle)
            ->with('success', 'Veículo restaurado com sucesso.');
    }
}
