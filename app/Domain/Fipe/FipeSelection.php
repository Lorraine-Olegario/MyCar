<?php

namespace App\Domain\Fipe;

/**
 * Escolha do usuário na cascata marca → modelo → ano/versão.
 */
final class FipeSelection
{
    public function __construct(
        public readonly FipeVehicleType $type,
        public readonly string $brandId,
        public readonly string $modelId,
        public readonly string $yearId,
    ) {}
}
