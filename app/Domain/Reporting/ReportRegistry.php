<?php

namespace App\Domain\Reporting;

use App\Domain\Reporting\Reports\CategoryReport;
use App\Domain\Reporting\Reports\ExpenseHistoryReport;
use App\Domain\Reporting\Reports\FipeEvolutionReport;
use App\Domain\Reporting\Reports\ForecastVsPaidReport;
use App\Domain\Reporting\Reports\FuelHistoryReport;
use App\Domain\Reporting\Reports\MonthlyByVehicleReport;
use App\Domain\Reporting\Reports\OwnershipCostReport;
use App\Domain\Reporting\Reports\Report;
use App\Domain\Reporting\Reports\SaleResultReport;
use App\Domain\Reporting\Reports\VehicleComparisonReport;
use App\Domain\Reporting\Reports\YearlyByVehicleReport;

/**
 * Catálogo dos relatórios disponíveis, na ordem de exibição.
 */
class ReportRegistry
{
    /**
     * @var list<class-string<Report>>
     */
    private const REPORTS = [
        MonthlyByVehicleReport::class,
        YearlyByVehicleReport::class,
        ExpenseHistoryReport::class,
        VehicleComparisonReport::class,
        CategoryReport::class,
        FuelHistoryReport::class,
        ForecastVsPaidReport::class,
        FipeEvolutionReport::class,
        OwnershipCostReport::class,
        SaleResultReport::class,
    ];

    /**
     * @return list<Report>
     */
    public function all(): array
    {
        return array_map(fn (string $class) => app($class), self::REPORTS);
    }

    public function find(string $key): ?Report
    {
        foreach ($this->all() as $report) {
            if ($report->key() === $key) {
                return $report;
            }
        }

        return null;
    }
}
