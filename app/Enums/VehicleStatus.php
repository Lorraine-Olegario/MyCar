<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Active = 'active';
    case Sold = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Sold => 'Vendido',
        };
    }
}
