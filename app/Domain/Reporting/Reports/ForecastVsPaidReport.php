<?php

namespace App\Domain\Reporting\Reports;

use App\Domain\Reporting\ReportFilters;
use App\Enums\OccurrenceStatus;
use App\Models\RecurringExpenseOccurrence;
use Carbon\CarbonImmutable;

/**
 * Relatório 7 — despesas recorrentes previstas vs pagas.
 *
 * Previsto (em aberto) e pago ficam em colunas e totais separados: uma previsão nunca é
 * somada ao realizado. Canceladas ficam de fora. Pagas entram pela data de pagamento;
 * previstas, pelo vencimento.
 */
class ForecastVsPaidReport extends Report
{
    public function key(): string
    {
        return 'forecast-vs-paid';
    }

    public function title(): string
    {
        return 'Despesas previstas vs pagas';
    }

    public function description(): string
    {
        return 'Recorrências com o valor previsto em aberto e o valor efetivamente pago, em colunas separadas.';
    }

    public function columns(ReportFilters $filters, array $rows): array
    {
        return [
            ['key' => 'due_date', 'label' => 'Vencimento', 'type' => 'date'],
            ['key' => 'vehicle', 'label' => 'Veículo', 'type' => 'text'],
            ['key' => 'description', 'label' => 'Descrição', 'type' => 'text'],
            ['key' => 'status', 'label' => 'Situação', 'type' => 'text'],
            ['key' => 'forecast_cents', 'label' => 'Previsto (em aberto)', 'type' => 'currency'],
            ['key' => 'paid_cents', 'label' => 'Pago', 'type' => 'currency'],
        ];
    }

    public function rows(ReportFilters $filters): array
    {
        $from = $filters->period->from->toDateString();
        $to = $filters->period->to->toDateString();

        $base = fn () => RecurringExpenseOccurrence::query()
            ->with('recurringExpense:id,vehicle_id,description', 'recurringExpense.vehicle:id,name')
            ->whereHas('recurringExpense', fn ($query) => $query
                ->whereIn('vehicle_id', $this->queries->vehicles($filters)->select('vehicles.id'))
                ->when($filters->categoryId, fn ($query, $id) => $query->where('expense_category_id', $id)));

        $paid = $base()->where('status', OccurrenceStatus::Paid)->whereBetween('paid_at', [$from, $to])->get();
        $forecast = $base()->forecast()->whereBetween('due_date', [$from, $to])->get();

        return $paid->concat($forecast)
            ->sortBy([fn ($a, $b) => $a->due_date->timestamp <=> $b->due_date->timestamp, fn ($a, $b) => $a->id <=> $b->id])
            ->map(fn (RecurringExpenseOccurrence $occurrence) => [
                'due_date' => $occurrence->due_date->toDateString(),
                'vehicle' => $occurrence->recurringExpense->vehicle?->name,
                'description' => $occurrence->recurringExpense->description,
                'status' => $occurrence->status->label(),
                'forecast_cents' => $occurrence->status === OccurrenceStatus::Forecast ? $occurrence->expected_amount_cents : null,
                'paid_cents' => $occurrence->status === OccurrenceStatus::Paid ? $occurrence->paid_amount_cents : null,
            ])
            ->values()
            ->all();
    }

    public function totals(ReportFilters $filters, array $rows): ?array
    {
        return [
            'due_date' => 'Total',
            'forecast_cents' => (int) array_sum(array_column($rows, 'forecast_cents')),
            'paid_cents' => (int) array_sum(array_column($rows, 'paid_cents')),
        ];
    }

    public function chart(ReportFilters $filters, array $rows): ?array
    {
        $data = $this->queries->forecastVsPaid($filters);

        if ($data['paid_cents'] === 0 && $data['forecast_cents'] === 0) {
            return null;
        }

        return [
            'type' => 'bar',
            'labels' => array_map(fn (array $month) => CarbonImmutable::createFromFormat('!Y-m', $month['month'])->translatedFormat('M/y'), $data['months']),
            'series' => [
                ['label' => 'Previsto (em aberto)', 'values' => array_column($data['months'], 'forecast_cents')],
                ['label' => 'Pago', 'values' => array_column($data['months'], 'paid_cents')],
            ],
        ];
    }

    public function paginated(): bool
    {
        return true;
    }

    public function defaultPeriod(): string
    {
        return 'this_year';
    }
}
