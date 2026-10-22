<?php

namespace App\Domain\Reporting\Reports;

use App\Domain\Reporting\ReportFilters;
use App\Enums\PaymentMethod;

/**
 * Relatório 3 — histórico completo de despesas.
 */
class ExpenseHistoryReport extends Report
{
    public function key(): string
    {
        return 'expense-history';
    }

    public function title(): string
    {
        return 'Histórico completo de despesas';
    }

    public function description(): string
    {
        return 'Todas as despesas do recorte, da mais recente para a mais antiga.';
    }

    public function columns(ReportFilters $filters, array $rows): array
    {
        return [
            ['key' => 'occurred_on', 'label' => 'Data', 'type' => 'date'],
            ['key' => 'vehicle', 'label' => 'Veículo', 'type' => 'text'],
            ['key' => 'category', 'label' => 'Categoria', 'type' => 'text'],
            ['key' => 'description', 'label' => 'Descrição', 'type' => 'text'],
            ['key' => 'vendor', 'label' => 'Fornecedor', 'type' => 'text'],
            ['key' => 'payment_method', 'label' => 'Pagamento', 'type' => 'text'],
            ['key' => 'amount_cents', 'label' => 'Valor', 'type' => 'currency'],
        ];
    }

    public function rows(ReportFilters $filters): array
    {
        return array_map(function (array $row) {
            unset($row['id']);
            $row['payment_method'] = $row['payment_method'] !== null ? PaymentMethod::tryFrom($row['payment_method'])?->label() : null;

            return $row;
        }, $this->queries->expenseRows($filters));
    }

    public function totals(ReportFilters $filters, array $rows): ?array
    {
        return ['occurred_on' => 'Total', 'amount_cents' => array_sum(array_column($rows, 'amount_cents'))];
    }

    public function paginated(): bool
    {
        return true;
    }

    public function defaultPeriod(): string
    {
        return 'all';
    }
}
