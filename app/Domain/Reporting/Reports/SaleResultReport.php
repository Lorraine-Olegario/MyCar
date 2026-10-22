<?php

namespace App\Domain\Reporting\Reports;

use App\Domain\Reporting\ReportFilters;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;

/**
 * Relatório 10 — resultado financeiro na venda.
 *
 * Resultado = venda − compra − despesas operacionais (entre a aquisição e a venda).
 * O valor FIPE não participa deste cálculo.
 */
class SaleResultReport extends Report
{
    public function key(): string
    {
        return 'sale-result';
    }

    public function title(): string
    {
        return 'Resultado financeiro na venda';
    }

    public function description(): string
    {
        return 'Para cada veículo vendido: compra, despesas operacionais, venda e o resultado. Sem compra ou venda informadas, o resultado fica em branco.';
    }

    public function adjustFilters(ReportFilters $filters): ReportFilters
    {
        return $filters->withScope(true, $filters->includeArchived);
    }

    public function columns(ReportFilters $filters, array $rows): array
    {
        return [
            ['key' => 'vehicle', 'label' => 'Veículo', 'type' => 'text'],
            ['key' => 'acquisition_date', 'label' => 'Aquisição', 'type' => 'date'],
            ['key' => 'sale_date', 'label' => 'Venda', 'type' => 'date'],
            ['key' => 'months_owned', 'label' => 'Meses', 'type' => 'number'],
            ['key' => 'purchase_cents', 'label' => 'Compra', 'type' => 'currency'],
            ['key' => 'operating_cents', 'label' => 'Despesas operacionais', 'type' => 'currency'],
            ['key' => 'sale_price_cents', 'label' => 'Valor de venda', 'type' => 'currency'],
            ['key' => 'sale_result_cents', 'label' => 'Resultado', 'type' => 'currency'],
        ];
    }

    public function rows(ReportFilters $filters): array
    {
        return $this->queries->vehicles($filters)
            ->where('status', VehicleStatus::Sold)
            ->orderBy('sale_date')
            ->orderBy('name')
            ->get()
            ->map(function (Vehicle $vehicle) {
                $summary = $this->queries->vehicleSummary($vehicle);

                return [
                    'vehicle' => $vehicle->name,
                    'acquisition_date' => $vehicle->acquisition_date->toDateString(),
                    'sale_date' => $vehicle->sale_date?->toDateString(),
                    'months_owned' => $summary['months_owned'],
                    'purchase_cents' => $summary['purchase_cents'],
                    'operating_cents' => $summary['operating_cents'],
                    'sale_price_cents' => $summary['sale_price_cents'],
                    'sale_result_cents' => $summary['sale_result_cents'],
                ];
            })
            ->all();
    }

    public function totals(ReportFilters $filters, array $rows): ?array
    {
        $known = array_filter(array_column($rows, 'sale_result_cents'), fn ($value) => $value !== null);

        return [
            'vehicle' => 'Total',
            'operating_cents' => (int) array_sum(array_column($rows, 'operating_cents')),
            'sale_result_cents' => $known === [] ? null : (int) array_sum($known),
        ];
    }

    public function chart(ReportFilters $filters, array $rows): ?array
    {
        $known = array_values(array_filter($rows, fn (array $row) => $row['sale_result_cents'] !== null));

        if ($known === []) {
            return null;
        }

        return [
            'type' => 'bar',
            'labels' => array_column($known, 'vehicle'),
            'series' => [['label' => 'Resultado', 'values' => array_column($known, 'sale_result_cents')]],
        ];
    }

    public function supports(): array
    {
        return ['category' => false, 'period' => false, 'scope' => false];
    }
}
