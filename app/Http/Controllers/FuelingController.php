<?php

namespace App\Http\Controllers;

use App\Domain\Fuel\FuelingRecorder;
use App\Http\Requests\StoreFuelingRequest;
use App\Http\Requests\UpdateFuelingRequest;
use App\Models\Fueling;
use Illuminate\Http\RedirectResponse;

class FuelingController extends Controller
{
    public function __construct(private readonly FuelingRecorder $recorder) {}

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
