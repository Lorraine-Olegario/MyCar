<?php

namespace App\Domain\Reporting;

use Carbon\CarbonImmutable;

/**
 * Formata relatórios para CSV compatível com Excel/LibreOffice pt-BR: UTF-8 com BOM,
 * separador `;`, decimal com vírgula, datas dd/mm/aaaa e cabeçalhos com a unidade.
 *
 * Valores ausentes saem como célula vazia (nunca zero). Textos que começariam uma
 * fórmula (=, +, -, @) são neutralizados para não serem executados pela planilha.
 */
class ReportCsv
{
    public const DELIMITER = ';';

    public const BOM = "\xEF\xBB\xBF";

    /**
     * @param  list<array{key: string, label: string, type: string}>  $columns
     * @return list<string>
     */
    public function header(array $columns): array
    {
        return array_map(fn (array $column) => $this->label($column), $columns);
    }

    /**
     * @param  list<array{key: string, label: string, type: string}>  $columns
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    public function line(array $columns, array $row): array
    {
        return array_map(fn (array $column) => $this->cell($column['type'], $row[$column['key']] ?? null), $columns);
    }

    /**
     * @param  array{key: string, label: string, type: string}  $column
     */
    public function label(array $column): string
    {
        $unit = match ($column['type']) {
            'currency', 'currency_decimal', 'price' => ' (R$)',
            'km' => ' (km)',
            'percent' => ' (%)',
            default => '',
        };

        return $this->text($column['label']).$unit;
    }

    public function cell(string $type, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return match ($type) {
            'currency' => $this->decimal($value / 100, 2),
            'currency_decimal' => $this->decimal($value / 100, 3),
            'price' => $this->decimal($value, 3),
            'date' => CarbonImmutable::parse($value)->format('d/m/Y'),
            'month' => CarbonImmutable::createFromFormat('!Y-m', (string) $value)->format('m/Y'),
            'year' => (string) $value,
            'number', 'km' => (string) (int) round((float) $value),
            'decimal2' => $this->decimal($value, 2),
            'decimal3' => $this->decimal($value, 3),
            'percent' => $this->decimal($value, 1),
            default => $this->text((string) $value),
        };
    }

    /**
     * Nome do arquivo: relatório + período (ou data da geração, quando não há período).
     *
     * @param  array<string, mixed>  $supports
     */
    public function filename(string $key, ReportFilters $filters, array $supports, ?CarbonImmutable $today = null): string
    {
        $today ??= CarbonImmutable::now()->startOfDay();

        $period = match (true) {
            ! $supports['period'] => $today->toDateString(),
            $filters->period->unbounded => 'todo-o-historico',
            default => $filters->period->from->toDateString().'_a_'.$filters->period->to->toDateString(),
        };

        return "mycar-{$key}-{$period}.csv";
    }

    private function decimal(int|float|string $value, int $digits): string
    {
        return number_format((float) $value, $digits, ',', '');
    }

    private function text(string $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);

        return preg_match('/^[=+\-@\t]/', $value) === 1 ? "'".$value : $value;
    }
}
