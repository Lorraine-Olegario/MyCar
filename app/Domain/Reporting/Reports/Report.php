<?php

namespace App\Domain\Reporting\Reports;

use App\Domain\Reporting\FinancialQueries;
use App\Domain\Reporting\ReportFilters;

/**
 * Relatório declarativo: colunas tipadas + linhas brutas (centavos, datas ISO, números).
 * A tela e a exportação CSV consomem exatamente a mesma definição, só mudando a formatação.
 *
 * Tipos de coluna: text, date, month, year, number, currency (centavos), currency_decimal
 * (centavos com fração), decimal2, decimal3, percent, km.
 */
abstract class Report
{
    public function __construct(protected readonly FinancialQueries $queries) {}

    abstract public function key(): string;

    abstract public function title(): string;

    abstract public function description(): string;

    /**
     * Colunas do relatório; podem depender do filtro (ex.: uma coluna por veículo).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{key: string, label: string, type: string}>
     */
    abstract public function columns(ReportFilters $filters, array $rows): array;

    /**
     * Todas as linhas do recorte (a paginação é feita por quem consome).
     *
     * @return list<array<string, mixed>>
     */
    abstract public function rows(ReportFilters $filters): array;

    /**
     * Linha de totais do recorte, ou null quando não faz sentido.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>|null
     */
    public function totals(ReportFilters $filters, array $rows): ?array
    {
        return null;
    }

    /**
     * Gráfico opcional: {type: line|bar|donut, labels, series: [{label, values}]} com valores em centavos.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>|null
     */
    public function chart(ReportFilters $filters, array $rows): ?array
    {
        return null;
    }

    /**
     * Listagens longas são paginadas na tela; a exportação sempre leva tudo.
     */
    public function paginated(): bool
    {
        return false;
    }

    /**
     * Permite ao relatório ajustar o escopo (ex.: TCO sempre inclui vendidos).
     */
    public function adjustFilters(ReportFilters $filters): ReportFilters
    {
        return $filters;
    }

    /**
     * Período inicial quando a URL não informa nenhum.
     */
    public function defaultPeriod(): string
    {
        return 'last_12_months';
    }

    /**
     * Quais filtros a tela oferece para este relatório.
     *
     * @return array{category: bool, period: bool, scope: bool}
     */
    public function supports(): array
    {
        return ['category' => true, 'period' => true, 'scope' => true];
    }
}
