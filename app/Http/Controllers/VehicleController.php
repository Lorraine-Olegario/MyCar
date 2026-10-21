<?php

namespace App\Http\Controllers;

use App\Domain\Reporting\Period;
use App\Domain\Reporting\VehicleFinancialOverview;
use App\Domain\Reporting\VehicleHistory;
use App\Domain\Vehicles\VehiclePhotoStorage;
use App\Enums\FuelType;
use App\Enums\VehicleCategory;
use App\Enums\VehicleStatus;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // `view` é o filtro histórico explícito: active, sold ou archived. Vazio = ativos e vendidos.
        $view = (string) $request->string('view');

        if ($view === 'archived') {
            $filters['archived'] = true;
        } elseif (in_array($view, ['active', 'sold'], true)) {
            $filters['status'] = $view;
        }

        $filters['view'] = $filters['archived'] ? 'archived' : ($filters['status'] !== '' ? $filters['status'] : '');

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
            'counts' => [
                'active' => Vehicle::query()->active()->count(),
                'sold' => Vehicle::query()->sold()->count(),
                'archived' => Vehicle::query()->archived()->count(),
            ],
            'options' => $this->options(),
        ]);
    }

    /**
     * Exibe um veículo.
     */
    public function show(Request $request, Vehicle $vehicle, VehicleFinancialOverview $financial, VehicleHistory $history): Response
    {
        // `financial` e `history` são calculados só quando pedidos (cargas parciais ao trocar de aba).
        return Inertia::render('Vehicles/Show', [
            'vehicle' => $vehicle,
            'financial' => fn () => $financial->build($vehicle, $request->query()),
            'history' => fn () => $history->build(
                $vehicle,
                $request->query(),
                $this->selectedPeriod($financial->build($vehicle, $request->query())['selection']),
            ),
            'options' => $this->options(),
        ]);
    }

    /**
     * @param  array{year: int, month: ?int}  $selection
     */
    private function selectedPeriod(array $selection): Period
    {
        return $selection['month'] !== null
            ? Period::ofMonth($selection['year'], $selection['month'])
            : Period::ofYear($selection['year']);
    }

    /**
     * Rótulos em português das listas fechadas usadas nas páginas de veículos.
     *
     * @return array{statuses: list<array{value: string, label: string}>, categories: list<array{value: string, label: string}>, fuels: list<array{value: string, label: string}>}
     */
    private function options(): array
    {
        return [
            'statuses' => $this->enumOptions(VehicleStatus::cases()),
            'categories' => $this->enumOptions(VehicleCategory::cases()),
            'fuels' => $this->enumOptions(FuelType::cases()),
        ];
    }

    /**
     * @param  list<VehicleStatus|VehicleCategory|FuelType>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function enumOptions(array $cases): array
    {
        return array_map(
            fn (VehicleStatus|VehicleCategory|FuelType $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            $cases,
        );
    }

    /**
     * Formulário de cadastro.
     */
    public function create(): Response
    {
        return Inertia::render('Vehicles/Form', [
            'vehicle' => null,
            'options' => $this->options(),
        ]);
    }

    /**
     * Formulário de edição.
     */
    public function edit(Vehicle $vehicle): Response
    {
        return Inertia::render('Vehicles/Form', [
            'vehicle' => $vehicle,
            'options' => $this->options(),
        ]);
    }

    /**
     * Cadastra um veículo.
     */
    public function store(StoreVehicleRequest $request, VehiclePhotoStorage $photos): RedirectResponse
    {
        $vehicle = Vehicle::create($request->safe()->except('photo'));

        $photo = $request->file('photo');

        if ($photo instanceof UploadedFile) {
            $vehicle->update(['photo_path' => $photos->put($vehicle, $photo)]);
        }

        return to_route('vehicles.show', $vehicle)
            ->with('success', 'Veículo cadastrado com sucesso.');
    }

    /**
     * Atualiza um veículo.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle, VehiclePhotoStorage $photos): RedirectResponse
    {
        $vehicle->update($request->safe()->except(['photo', 'remove_photo']));

        $photo = $request->file('photo');

        if ($photo instanceof UploadedFile) {
            $vehicle->update(['photo_path' => $photos->put($vehicle, $photo)]);
        } elseif ($request->boolean('remove_photo')) {
            $photos->forget($vehicle);
            $vehicle->update(['photo_path' => null]);
        }

        return to_route('vehicles.show', $vehicle)
            ->with('success', 'Veículo atualizado com sucesso.');
    }

    /**
     * Arquiva (soft delete) um veículo; nada é apagado fisicamente, inclusive
     * a foto, que é mantida no disco privado para o histórico.
     */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return to_route('vehicles.index')
            ->with('success', 'Veículo arquivado com sucesso.');
    }

    /**
     * Serve a foto do veículo a partir do disco privado.
     */
    public function photo(Vehicle $vehicle, VehiclePhotoStorage $photos): StreamedResponse
    {
        abort_unless($photos->exists($vehicle), 404);

        return Storage::disk(VehiclePhotoStorage::DISK)->response($vehicle->photo_path);
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
