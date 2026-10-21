<?php

namespace App\Domain\Reporting;

use App\Domain\Reporting\Reports\Report;
use App\Models\ExpenseCategory;
use App\Models\Vehicle;
use Carbon\CarbonInterface;

/**
 * Executa um relatório para uma query string e devolve tudo o que a tela e o CSV precisam,
 * incluindo a descrição dos filtros aplicados (período, veículo e categoria).
 */
class ReportBuilder
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{report: Report, filters: ReportFilters, echo: array<string, string>, columns: list<array<string, string>>, rows: list<array<string, mixed>>, totals: array<string, mixed>|null, chart: array<string, mixed>|null, applied: list<array{label: string, value: string}>}
     */
    public function build(Report $report, array $input, ?CarbonInterface $today = null): array
    {
        [$filters, $echo] = ReportFilters::fromInput($input, $today, $report->defaultPeriod());

        $rows = $report->rows($filters);

        return [
            'report' => $report,
            'filters' => $filters,
            'echo' => $echo,
            'columns' => $report->columns($filters, $rows),
            'rows' => $rows,
            'totals' => $report->totals($filters, $rows),
            'chart' => $report->chart($filters, $rows),
            'applied' => $this->applied($filters),
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function applied(ReportFilters $filters): array
    {
        $period = $filters->period->unbounded
            ? 'Todo o histórico'
            : $filters->period->from->format('d/m/Y').' a '.$filters->period->to->format('d/m/Y');

        $vehicle = $filters->vehicleId !== null
            ? (Vehicle::withTrashed()->find($filters->vehicleId)?->full_name ?? 'Veículo não encontrado')
            : ($filters->includeSold ? 'Ativos e vendidos' : 'Veículos ativos');

        return [
            ['label' => 'Período', 'value' => $period],
            ['label' => 'Veículo', 'value' => $vehicle.($filters->includeArchived && $filters->vehicleId === null ? ' (com arquivados)' : '')],
            ['label' => 'Categoria', 'value' => $filters->categoryId !== null ? (ExpenseCategory::query()->find($filters->categoryId)?->name ?? 'Categoria não encontrada') : 'Todas'],
        ];
    }
}
