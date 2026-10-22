<?php

namespace App\Domain\Reporting;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Dados financeiros da página do veículo, todos derivados de {@see FinancialQueries}.
 *
 * Compra e despesas operacionais ficam separadas: o TCO as soma explicitamente, mas as
 * despesas por período/categoria nunca incluem o valor de compra.
 */
class VehicleFinancialOverview
{
    public function __construct(private readonly FinancialQueries $queries) {}

    /**
     * @param  array<string, mixed>  $input  query string (year, month)
     * @return array<string, mixed>
     */
    public function build(Vehicle $vehicle, array $input, ?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        $years = $this->years($vehicle, $today);
        $validYears = array_column($years, 'value');

        $defaultYear = in_array($today->year, $validYears, true) ? $today->year : $validYears[0];
        $year = isset($input['year']) && ctype_digit((string) $input['year']) && in_array((int) $input['year'], $validYears, true)
            ? (int) $input['year']
            : $defaultYear;

        $month = isset($input['month']) && ctype_digit((string) $input['month']) && (int) $input['month'] >= 1 && (int) $input['month'] <= 12
            ? (int) $input['month']
            : null;

        $yearFilters = new ReportFilters(Period::ofYear($year), $vehicle->id);
        $monthFilters = $month !== null ? new ReportFilters(Period::ofMonth($year, $month), $vehicle->id) : null;
        $selected = $monthFilters ?? $yearFilters;

        return [
            'summary' => $this->queries->vehicleSummary($vehicle, $today),
            'selection' => ['year' => $year, 'month' => $month],
            'years' => $years,
            'year_totals' => $this->queries->totals($yearFilters),
            'month_totals' => $monthFilters ? $this->queries->totals($monthFilters) : null,
            'monthly' => $this->queries->monthlySeries($yearFilters),
            'categories' => $this->queries->byCategory($selected),
            'completeness' => $this->queries->completeness($selected),
        ];
    }

    /**
     * Anos selecionáveis: da aquisição até hoje (ou a venda), marcando os que começam
     * antes do início do acompanhamento como histórico incompleto.
     *
     * @return list<array{value: int, label: string, incomplete: bool}>
     */
    private function years(Vehicle $vehicle, CarbonImmutable $today): array
    {
        $first = CarbonImmutable::instance($vehicle->acquisition_date)->year;
        $last = $vehicle->status === VehicleStatus::Sold && $vehicle->sale_date
            ? CarbonImmutable::instance($vehicle->sale_date)->year
            : $today->year;
        $last = max($first, $last);
        $tracking = $vehicle->tracking_start_date ? CarbonImmutable::instance($vehicle->tracking_start_date)->startOfDay() : null;

        $years = [];

        for ($year = $last; $year >= $first; $year--) {
            $incomplete = $tracking === null || $tracking->gt(CarbonImmutable::create($year, 1, 1)->startOfDay());

            $years[] = [
                'value' => $year,
                'label' => $incomplete ? "{$year} (histórico incompleto)" : (string) $year,
                'incomplete' => $incomplete,
            ];
        }

        return $years;
    }
}
