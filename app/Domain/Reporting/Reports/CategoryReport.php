<?php

namespace App\Domain\Reporting\Reports;

use App\Domain\Reporting\ReportFilters;

/**
 * Relatório 5 — gastos por categoria.
 */
class CategoryReport extends Report
{
    public function key(): string
    {
        return 'by-category';
    }

    public function title(): string
    {
        return 'Gastos por categoria';
    }

    public function description(): string
    {
        return 'Quanto foi gasto em cada categoria e a participação no total do recorte.';
    }

    public function columns(ReportFilters $filters, array $rows): array
    {
        return [
            ['key' => 'name', 'label' => 'Categoria', 'type' => 'text'],
            ['key' => 'count', 'label' => 'Despesas', 'type' => 'number'],
            ['key' => 'total_cents', 'label' => 'Total', 'type' => 'currency'],
            ['key' => 'share', 'label' => 'Participação', 'type' => 'percent'],
        ];
    }

    public function rows(ReportFilters $filters): array
    {
        return array_map(fn (array $row) => [
            'name' => $row['name'],
            'count' => $row['count'],
            'total_cents' => $row['total_cents'],
            'share' => $row['share'],
        ], $this->queries->byCategory($filters));
    }

    public function totals(ReportFilters $filters, array $rows): ?array
    {
        $total = array_sum(array_column($rows, 'total_cents'));

        return [
            'name' => 'Total',
            'count' => array_sum(array_column($rows, 'count')),
            'total_cents' => $total,
            'share' => $total > 0 ? 100.0 : null,
        ];
    }

    public function chart(ReportFilters $filters, array $rows): ?array
    {
        if (array_sum(array_column($rows, 'total_cents')) === 0) {
            return null;
        }

        return [
            'type' => 'donut',
            'labels' => array_column($rows, 'name'),
            'series' => [['label' => 'Total', 'values' => array_column($rows, 'total_cents')]],
        ];
    }
}
