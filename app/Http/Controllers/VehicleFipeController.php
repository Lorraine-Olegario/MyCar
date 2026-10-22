<?php

namespace App\Http\Controllers;

use App\Domain\Fipe\FipeClient;
use App\Domain\Fipe\FipeException;
use App\Domain\Fipe\FipeOption;
use App\Domain\Fipe\FipeQuoteService;
use App\Domain\Fipe\FipeSelection;
use App\Domain\Fipe\FipeVehicleType;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleFipeController extends Controller
{
    public function __construct(
        private readonly FipeClient $client,
        private readonly FipeQuoteService $service,
    ) {}

    /**
     * Opções do catálogo para a cascata marca → modelo → ano/versão (JSON, só leitura).
     * Independem do veículo: também servem ao cadastro de um veículo novo.
     */
    public function brands(Request $request): JsonResponse
    {
        $type = $this->type($request);

        return $this->catalog(fn () => $this->client->brands($type));
    }

    public function models(Request $request): JsonResponse
    {
        $type = $this->type($request);
        $brand = $request->validate(['brand' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/']])['brand'];

        return $this->catalog(fn () => $this->client->models($type, $brand));
    }

    public function years(Request $request): JsonResponse
    {
        $type = $this->type($request);
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
            'model' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
        ]);

        return $this->catalog(fn () => $this->client->years($type, $data['brand'], $data['model']));
    }

    /**
     * Consulta o valor para a seleção escolhida e registra no histórico.
     */
    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(FipeVehicleType::class)],
            'brand_id' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
            'model_id' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
            'year_id' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
        ], [
            'type.required' => 'Escolha o tipo de veículo.',
            'brand_id.required' => 'Escolha a marca.',
            'model_id.required' => 'Escolha o modelo.',
            'year_id.required' => 'Escolha o ano/versão.',
        ]);

        $selection = new FipeSelection(FipeVehicleType::from($data['type']), $data['brand_id'], $data['model_id'], $data['year_id']);

        return $this->run($vehicle, fn () => $this->service->consult($vehicle, $selection));
    }

    /**
     * Atualiza a cotação com a seleção já guardada no veículo.
     */
    public function refresh(Vehicle $vehicle): RedirectResponse
    {
        return $this->run($vehicle, fn () => $this->service->refresh($vehicle));
    }

    private function run(Vehicle $vehicle, callable $action): RedirectResponse
    {
        try {
            $action();
        } catch (FipeException $exception) {
            // O valor anterior fica intacto; a página mostra o erro e o aviso de desatualização.
            return to_route('vehicles.show', $vehicle)->withErrors(['fipe' => $exception->getMessage()]);
        }

        return to_route('vehicles.show', $vehicle)->with('success', 'Cotação FIPE atualizada.');
    }

    private function type(Request $request): FipeVehicleType
    {
        return FipeVehicleType::from($request->validate(['type' => ['required', Rule::enum(FipeVehicleType::class)]])['type']);
    }

    /**
     * @param  callable(): list<FipeOption>  $loader
     */
    private function catalog(callable $loader): JsonResponse
    {
        try {
            return response()->json(['data' => array_map(fn (FipeOption $option) => $option->toArray(), $loader())]);
        } catch (FipeException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'reason' => $exception->reason], match ($exception->reason) {
                FipeException::NOT_FOUND => 404,
                FipeException::MALFORMED => 502,
                default => 503,
            });
        }
    }
}
