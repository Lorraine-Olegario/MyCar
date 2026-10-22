<?php

namespace App\Domain\Reporting\Reports;

use App\Domain\Reporting\ReportFilters;
use App\Models\Vehicle;

/**
 * Relatório 9 — custo total de propriedade (TCO) por veículo.
 *
 * TCO = compra + despesas operacionais; a compra aparece em coluna própria e nunca é
 * tratada como despesa operacional. Não depende de período: cobre toda a propriedade.
 */
class OwnershipCostReport extends Report
{
    public function key(): string
    {
        return 'ownership-cost';
    }

    public function title(): string
    {
        return 'Custo total de propriedade (TCO)';
    }

    public function description(): string
    {
        return 'Compra + despesas operacionais de cada veículo, com custo médio mensal e custo por km quando há dados suficientes.';
    }

    public function adjustFilters(ReportFilters $filters): ReportFilters
    {
        return $filters->withScope(true, $filters->includeArchived);
    }

    public function columns(ReportFilters $filters, array $rows): array
    {
        return [
            ['key' => 'vehicle', 'label' => 'Veículo', 'type' => 'text'],
            ['key' => 'status', 'label' => 'Situação', 'type' => 'text'],
            ['key' => 'purchase_cents', 'label' => 'Compra', 'type' => 'currency'],
            ['key' => 'operating_cents', 'label' => 'Despesas operacionais', 'type' => 'currency'],
            ['key' => 'tco_cents', 'label' => 'TCO', 'type' => 'currency'],
            ['key' => 'months_owned', 'label' => 'Meses', 'type' => 'number'],
            ['key' => 'average_monthly_cost_cents', 'label' => 'Custo médio mensal', 'type' => 'currency'],
            ['key' => 'average_monthly_operating_cents', 'label' => 'Média operacional mensal', 'type' => 'currency'],
            ['key' => 'distance_km', 'label' => 'Km rodados', 'type' => 'km'],
            ['key' => 'cost_per_km_cents', 'label' => 'Custo por km', 'type' => 'currency_decimal'],
        ];
    }

    public function rows(ReportFilters $filters): array
    {
        return $this->queries->vehicles($filters)->orderBy('name')->get()->map(function (Vehicle $vehicle) {
            $summary = $this->queries->vehicleSummary($vehicle);

            return [
                'vehicle' => $vehicle->name,
                'status' => $vehicle->trashed() ? 'Arquivado' : $vehicle->status->label(),
                'purchase_cents' => $summary['purchase_cents'],
                'operating_cents' => $summary['operating_cents'],
                'tco_cents' => $summary['tco_cents'],
                'months_owned' => $summary['months_owned'],
                'average_monthly_cost_cents' => $summary['average_monthly_cost_cents'],
                'average_monthly_operating_cents' => $summary['average_monthly_operating_cents'],
                'distance_km' => $summary['distance_km'],
                'cost_per_km_cents' => $summary['cost_per_km_cents'],
            ];
        })->all();
    }

    public function totals(ReportFilters $filters, array $rows): ?array
    {
        return [
            'vehicle' => 'Total',
            'purchase_cents' => (int) array_sum(array_column($rows, 'purchase_cents')),
            'operating_cents' => (int) array_sum(array_column($rows, 'operating_cents')),
            'tco_cents' => (int) array_sum(array_column($rows, 'tco_cents')),
        ];
    }

    public function chart(ReportFilters $filters, array $rows): ?array
    {
        if ($rows === [] || array_sum(array_column($rows, 'tco_cents')) === 0) {
            return null;
        }

        return [
            'type' => 'bar',
            'labels' => array_column($rows, 'vehicle'),
            'series' => [
                ['label' => 'Compra', 'values' => array_map(fn ($value) => $value ?? 0, array_column($rows, 'purchase_cents'))],
                ['label' => 'Despesas operacionais', 'values' => array_column($rows, 'operating_cents')],
            ],
        ];
    }

    public function supports(): array
    {
        return ['category' => false, 'period' => false, 'scope' => false];
    }
}
