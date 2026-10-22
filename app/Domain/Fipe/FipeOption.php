<?php

namespace App\Domain\Fipe;

/**
 * Item de catálogo da FIPE (marca, modelo ou ano/versão).
 */
final class FipeOption
{
    public function __construct(public readonly string $code, public readonly string $name) {}

    /**
     * @return array{code: string, name: string}
     */
    public function toArray(): array
    {
        return ['code' => $this->code, 'name' => $this->name];
    }
}
