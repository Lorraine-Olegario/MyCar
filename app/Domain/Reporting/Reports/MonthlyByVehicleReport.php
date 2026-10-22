<?php

namespace App\Domain\Reporting\Reports;

use App\Domain\Reporting\ReportFilters;
use Carbon\CarbonImmutable;

/**
 * Relatório 1 — gastos mensais por veículo.
 */
class MonthlyByVehicleReport extends Report
{
    protected string $format = 'Y-m';

    protected string $columnType = 'month';

    protected string $columnLabel = 'Mês';

    public function key(): string
    {
        return 'monthly-by-vehicle';
    }

    public function title(): string
    {
        return 'Gastos mensais por veículo';
    }

    public function description(): string
    {
        return 'Despesas realizadas em cada mês, com uma coluna por veículo.';
    }

    public function columns(ReportFilters $filters, array $rows): array
    {
        $data = $this->queries->seriesByVehicle($filters, $this->format);

        return [
            ['key' => 'period', 'label' => $this->columnLabel, 'type' => $this->columnType],
            ...array_map(fn (int $id, string $name) => ['key' => "v{$id}", 'label' => $name, 'type' => 'currency'], array_keys($data['vehicles']), $data['vehicles']),
            ['key' => 'total_cents', 'label' => 'Total', 'type' => 'currency'],
        ];
    }

    public function rows(ReportFilters $filters): array
    {
        $data = $this->queries->seriesByVehicle($filters, $this->format);

        return array_map(function (string $bucket) use ($data) {
            $row = ['period' => $bucket, 'total_cents' => 0];

            foreach ($data['vehicles'] as $id => $name) {
                $row["v{$id}"] = $data['cells'][$bucket][$id] ?? 0;
                $row['total_cents'] += $row["v{$id}"];
            }

            return $row;
        }, $data['buckets']);
    }

    public function totals(ReportFilters $filters, array $rows): ?array
    {
        $totals = ['period' => 'Total'];

        foreach ($this->columns($filters, $rows) as $column) {
            if ($column['type'] === 'currency') {
                $totals[$column['key']] = array_sum(array_column($rows, $column['key']));
            }
        }

        return $totals;
    }

    public function chart(ReportFilters $filters, array $rows): ?array
    {
        $columns = array_values(array_filter($this->columns($filters, $rows), fn (array $column) => str_starts_with($column['key'], 'v')));

        if ($columns === [] || array_sum(array_column($rows, 'total_cents')) === 0) {
            return null;
        }

        return [
            'type' => 'bar',
            'labels' => array_map(fn (array $row) => $this->label($row['period']), $rows),
            'series' => array_map(fn (array $column) => [
                'label' => $column['label'],
                'values' => array_column($rows, $column['key']),
            ], $columns),
        ];
    }

    public function supports(): array
    {
        return ['category' => true, 'period' => true, 'scope' => true];
    }

    private function label(string $bucket): string
    {
        return $this->format === 'Y' ? $bucket : CarbonImmutable::createFromFormat('!Y-m', $bucket)->translatedFormat('M/y');
    }
}
