<?php

namespace App\Http\Controllers;

use App\Domain\Fuel\FuelingOverview;
use App\Domain\Fuel\FuelingRecorder;
use App\Enums\FuelType;
use App\Http\Requests\StoreFuelingRequest;
use App\Http\Requests\UpdateFuelingRequest;
use App\Models\Fueling;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FuelingController extends Controller
{
    public function __construct(private readonly FuelingRecorder $recorder) {}

    /**
     * Lista abastecimentos (veículo e período, 20 por página) com totais do filtro,
     * consumo por abastecimento e totais mensais/anuais.
     */
    public function index(Request $request, FuelingOverview $overview): Response
    {
        $filters = [
            'vehicle_id' => (string) $request->string('vehicle_id'),
            'from' => (string) $request->string('from'),
            'to' => (string) $request->string('to'),
        ];

        $query = Fueling::query()
            ->when($filters['vehicle_id'] !== '', fn ($query) => $query->where('vehicle_id', $filters['vehicle_id']))
            ->when($filters['from'] !== '', fn ($query) => $query->whereDate('fueled_on', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->whereDate('fueled_on', '<=', $filters['to']));

        $filtered = (clone $query)->get();
        $segments = $overview->segmentsFor($filtered);

        $fuelings = $query
            ->with('vehicle:id,name,deleted_at')
            ->orderByDesc('fueled_on')
            ->orderByDesc('odometer')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Fueling $fueling) => [
                ...$fueling->toArray(),
                'stats' => $segments[$fueling->id] ?? null,
            ]);

        return Inertia::render('Fuelings/Index', [
            'fuelings' => $fuelings,
            'filters' => $filters,
            'summary' => $overview->summary($filtered, $segments),
            'monthly' => $overview->totalsBy($filtered, 'Y-m'),
            'yearly' => $overview->totalsBy($filtered, 'Y'),
            'options' => [
                'vehicles' => Vehicle::query()->orderBy('name')->get(['id', 'name', 'brand', 'model', 'version', 'fuel', 'odometer'])
                    ->map(fn (Vehicle $vehicle) => [
                        'value' => $vehicle->id,
                        'label' => $vehicle->full_name,
                        'fuel' => $vehicle->fuel?->value,
                        'odometer' => $vehicle->odometer,
                    ])->values(),
                'fuel_types' => array_map(
                    fn (FuelType $type) => ['value' => $type->value, 'label' => $type->label()],
                    FuelType::cases(),
                ),
            ],
        ]);
    }

    public function store(StoreFuelingRequest $request): RedirectResponse
    {
        $this->recorder->store($request->validated());

        return to_route('fuelings.index')->with('success', 'Abastecimento registrado com sucesso.');
    }

    public function update(UpdateFuelingRequest $request, Fueling $fueling): RedirectResponse
    {
        $this->recorder->update($fueling, $request->validated());

        return to_route('fuelings.index')->with('success', 'Abastecimento atualizado com sucesso.');
    }

    public function destroy(Fueling $fueling): RedirectResponse
    {
        $fueling->delete();

        return to_route('fuelings.index')->with('success', 'Abastecimento excluído com sucesso.');
    }
}
