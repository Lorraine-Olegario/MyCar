<?php

namespace App\Domain\Reporting\Reports;

use App\Domain\Reporting\ReportFilters;
use App\Models\FipeQuote;
use Carbon\CarbonImmutable;

/**
 * Relatório 8 — evolução do valor FIPE por veículo.
 *
 * Mostra as consultas realmente feitas (nada é interpolado). Quando o mês de referência
 * pula meses entre duas consultas, a linha avisa a lacuna. A FIPE é referência de mercado e
 * não se mistura aos gastos.
 */
class FipeEvolutionReport extends Report
{
    public function key(): string
    {
        return 'fipe-evolution';
    }

    public function title(): string
    {
        return 'Evolução do valor FIPE';
    }

    public function description(): string
    {
        return 'Cada consulta FIPE por veículo, com variação em relação à consulta anterior e aviso de lacunas entre meses de referência.';
    }

    public function columns(ReportFilters $filters, array $rows): array
    {
        return [
            ['key' => 'consulted_on', 'label' => 'Consulta', 'type' => 'date'],
            ['key' => 'vehicle', 'label' => 'Veículo', 'type' => 'text'],
            ['key' => 'reference_month', 'label' => 'Mês de referência', 'type' => 'month'],
            ['key' => 'model', 'label' => 'Modelo FIPE', 'type' => 'text'],
            ['key' => 'value_cents', 'label' => 'Valor FIPE', 'type' => 'currency'],
            ['key' => 'change_cents', 'label' => 'Variação', 'type' => 'currency'],
            ['key' => 'change_percent', 'label' => 'Variação %', 'type' => 'percent'],
            ['key' => 'gap', 'label' => 'Lacuna', 'type' => 'text'],
        ];
    }

    /**
     * Consultas do período, com variação calculada sobre o histórico completo do veículo
     * (a variação da primeira consulta do recorte compara com a anterior real, mesmo fora dele).
     */
    public function rows(ReportFilters $filters): array
    {
        $quotes = FipeQuote::query()
            ->with('vehicle:id,name')
            ->whereIn('vehicle_id', $this->queries->vehicles($filters)->select('vehicles.id'))
            ->orderBy('consulted_at')
            ->orderBy('id')
            ->get()
            ->groupBy('vehicle_id');

        $rows = [];

        foreach ($quotes as $vehicleQuotes) {
            $previous = null;

            foreach ($vehicleQuotes as $quote) {
                $consultedOn = $quote->consulted_at->toDateString();

                if ($filters->period->contains($consultedOn)) {
                    $change = $previous ? $quote->value_cents - $previous->value_cents : null;

                    $rows[] = [
                        'consulted_on' => $consultedOn,
                        'consulted_at' => $quote->consulted_at->timestamp,
                        'vehicle' => $quote->vehicle?->name,
                        'reference_month' => $quote->reference_month,
                        'model' => $quote->model,
                        'value_cents' => $quote->value_cents,
                        'change_cents' => $change,
                        'change_percent' => $previous && $previous->value_cents > 0 ? round($change / $previous->value_cents * 100, 1) : null,
                        'gap' => $previous ? $this->gap($previous->reference_month, $quote->reference_month) : null,
                    ];
                }

                $previous = $quote;
            }
        }

        usort($rows, fn (array $a, array $b) => [$b['consulted_at'], $a['vehicle']] <=> [$a['consulted_at'], $b['vehicle']]);

        return array_map(function (array $row) {
            unset($row['consulted_at']);

            return $row;
        }, $rows);
    }

    public function chart(ReportFilters $filters, array $rows): ?array
    {
        if ($rows === []) {
            return null;
        }

        $months = array_values(array_unique(array_column($rows, 'reference_month')));
        sort($months);

        $series = [];

        // Do mais antigo para o mais novo: a consulta mais recente do mês prevalece.
        foreach (array_reverse($rows) as $row) {
            $series[$row['vehicle']][$row['reference_month']] = $row['value_cents'];
        }

        return [
            'type' => 'line',
            'labels' => $months,
            'series' => array_map(
                fn (string $vehicle) => ['label' => $vehicle, 'values' => array_map(fn (string $month) => $series[$vehicle][$month] ?? null, $months)],
                array_keys($series),
            ),
        ];
    }

    public function paginated(): bool
    {
        return true;
    }

    public function defaultPeriod(): string
    {
        return 'all';
    }

    public function supports(): array
    {
        return ['category' => false, 'period' => true, 'scope' => true];
    }

    private function gap(string $from, string $to): ?string
    {
        $a = CarbonImmutable::createFromFormat('!Y-m', $from);
        $b = CarbonImmutable::createFromFormat('!Y-m', $to);
        $missing = (int) $a->diffInMonths($b) - 1;

        return $missing > 0 ? "Lacuna de {$missing} ".($missing === 1 ? 'mês' : 'meses')." sem consulta ({$from} → {$to})" : null;
    }
}
