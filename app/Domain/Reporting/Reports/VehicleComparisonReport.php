<?php

namespace App\Domain\Reporting\Reports;

use App\Domain\Reporting\ReportFilters;

/**
 * Relatório 4 — comparativo entre veículos.
 */
class VehicleComparisonReport extends Report
{
    public function key(): string
    {
        return 'vehicle-comparison';
    }

    public function title(): string
    {
        return 'Comparativo entre veículos';
    }

    public function description(): string
    {
        return 'Total gasto, quantidade de despesas, média por despesa e participação de cada veículo.';
    }

    public function columns(ReportFilters $filters, array $rows): array
    {
        return [
            ['key' => 'name', 'label' => 'Veículo', 'type' => 'text'],
            ['key' => 'count', 'label' => 'Despesas', 'type' => 'number'],
            ['key' => 'total_cents', 'label' => 'Total', 'type' => 'currency'],
            ['key' => 'average_cents', 'label' => 'Média por despesa', 'type' => 'currency'],
            ['key' => 'share', 'label' => 'Participação', 'type' => 'percent'],
        ];
    }

    public function rows(ReportFilters $filters): array
    {
        return array_map(fn (array $row) => [
            'name' => $row['name'],
            'count' => $row['count'],
            'total_cents' => $row['total_cents'],
            'average_cents' => $row['count'] > 0 ? (int) round($row['total_cents'] / $row['count']) : null,
            'share' => $row['share'],
        ], $this->queries->byVehicle($filters));
    }

    public function totals(ReportFilters $filters, array $rows): ?array
    {
        $count = array_sum(array_column($rows, 'count'));
        $total = array_sum(array_column($rows, 'total_cents'));

        return [
            'name' => 'Total',
            'count' => $count,
            'total_cents' => $total,
            'average_cents' => $count > 0 ? (int) round($total / $count) : null,
            'share' => $total > 0 ? 100.0 : null,
        ];
    }

    public function chart(ReportFilters $filters, array $rows): ?array
    {
        if (array_sum(array_column($rows, 'total_cents')) === 0) {
            return null;
        }

        return [
            'type' => 'bar',
            'labels' => array_column($rows, 'name'),
            'series' => [['label' => 'Total', 'values' => array_column($rows, 'total_cents')]],
        ];
    }
}
