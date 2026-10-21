<?php

namespace App\Domain\Reporting;

use Carbon\CarbonInterface;

/**
 * Filtros comuns a todas as consultas: período, veículo, categoria e escopo de status.
 *
 * Sem `vehicleId`, só entram veículos ativos e não arquivados. Veículos vendidos e
 * arquivados são consultas históricas: entram ao serem pedidos explicitamente (por id
 * ou pelas flags) e, no caso dos vendidos, apenas no intervalo aquisição → venda.
 */
final class ReportFilters
{
    public function __construct(
        public readonly Period $period,
        public readonly ?int $vehicleId = null,
        public readonly ?int $categoryId = null,
        public readonly bool $includeSold = false,
        public readonly bool $includeArchived = false,
    ) {}

    /**
     * Monta os filtros a partir da query string e devolve também o eco normalizado
     * (para a interface refletir exatamente o que foi aplicado).
     *
     * @param  array<string, mixed>  $input
     * @return array{0: self, 1: array{period: string, from: string, to: string, vehicle_id: string, category_id: string}}
     */
    public static function fromInput(array $input, ?CarbonInterface $today = null, string $defaultPeriod = 'last_12_months'): array
    {
        $preset = (string) ($input['period'] ?? $defaultPeriod);
        $preset = in_array($preset, ['last_12_months', 'this_month', 'this_year', 'last_year', 'all', 'custom'], true) ? $preset : $defaultPeriod;

        $from = isset($input['from']) ? (string) $input['from'] : null;
        $to = isset($input['to']) ? (string) $input['to'] : null;

        // Personalizado inválido volta ao padrão; o eco informa o período realmente usado.
        if ($preset === 'custom' && Period::tryCustom($from, $to) === null) {
            $preset = $defaultPeriod;
        }

        $period = Period::fromPreset($preset, $from, $to, $today);

        $vehicleId = isset($input['vehicle_id']) && ctype_digit((string) $input['vehicle_id']) ? (int) $input['vehicle_id'] : null;
        $categoryId = isset($input['category_id']) && ctype_digit((string) $input['category_id']) ? (int) $input['category_id'] : null;

        return [
            new self(
                $period,
                $vehicleId,
                $categoryId,
                filter_var($input['include_sold'] ?? false, FILTER_VALIDATE_BOOLEAN),
                filter_var($input['include_archived'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ),
            [
                'period' => $preset,
                'from' => $preset === 'custom' ? $period->from->toDateString() : '',
                'to' => $preset === 'custom' ? $period->to->toDateString() : '',
                'vehicle_id' => $vehicleId !== null ? (string) $vehicleId : '',
                'category_id' => $categoryId !== null ? (string) $categoryId : '',
            ],
        ];
    }

    public function withPeriod(Period $period): self
    {
        return new self($period, $this->vehicleId, $this->categoryId, $this->includeSold, $this->includeArchived);
    }

    public function withVehicle(?int $vehicleId): self
    {
        return new self($this->period, $vehicleId, $this->categoryId, $this->includeSold, $this->includeArchived);
    }
}
