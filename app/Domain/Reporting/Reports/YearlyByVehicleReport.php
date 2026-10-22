<?php

namespace App\Domain\Reporting\Reports;

/**
 * Relatório 2 — gastos anuais por veículo.
 */
class YearlyByVehicleReport extends MonthlyByVehicleReport
{
    protected string $format = 'Y';

    protected string $columnType = 'year';

    protected string $columnLabel = 'Ano';

    public function key(): string
    {
        return 'yearly-by-vehicle';
    }

    public function title(): string
    {
        return 'Gastos anuais por veículo';
    }

    public function description(): string
    {
        return 'Despesas realizadas em cada ano, com uma coluna por veículo.';
    }

    public function defaultPeriod(): string
    {
        return 'all';
    }
}
