<?php

namespace App\Domain\Fipe;

/**
 * Cotação devolvida pela FIPE. O mês de referência vem da própria fonte (`YYYY-MM`).
 */
final class FipeQuoteData
{
    /**
     * @param  array<string, mixed>  $payload  resposta original, guardada no histórico
     */
    public function __construct(
        public readonly string $brand,
        public readonly string $model,
        public readonly int $modelYear,
        public readonly string $fuel,
        public readonly string $codeFipe,
        public readonly int $valueCents,
        public readonly string $referenceMonth,
        public readonly ?string $vehicleType = null,
        public readonly array $payload = [],
    ) {}
}
