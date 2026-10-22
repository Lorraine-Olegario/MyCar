<?php

namespace App\Http\Controllers;

use App\Domain\Reporting\DashboardOverview;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Visão geral: veículo em destaque, indicadores, gráficos e vencimentos, com filtros
     * de veículo e período refletidos na URL.
     */
    public function __invoke(Request $request, DashboardOverview $overview): Response
    {
        return Inertia::render('Dashboard', $overview->build($request->query()));
    }
}
