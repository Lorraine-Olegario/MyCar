<?php

namespace App\Domain\Reporting;

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

    public function withPeriod(Period $period): self
    {
        return new self($period, $this->vehicleId, $this->categoryId, $this->includeSold, $this->includeArchived);
    }

    public function withVehicle(?int $vehicleId): self
    {
        return new self($this->period, $vehicleId, $this->categoryId, $this->includeSold, $this->includeArchived);
    }
}
