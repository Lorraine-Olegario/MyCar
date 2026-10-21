<?php

namespace App\Enums;

enum FuelType: string
{
    case Gasoline = 'gasoline';
    case Ethanol = 'ethanol';
    case Flex = 'flex';
    case Diesel = 'diesel';
    case Cng = 'cng';
    case Electric = 'electric';
    case Hybrid = 'hybrid';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Gasoline => 'Gasolina',
            self::Ethanol => 'Etanol',
            self::Flex => 'Flex',
            self::Diesel => 'Diesel',
            self::Cng => 'GNV',
            self::Electric => 'Elétrico',
            self::Hybrid => 'Híbrido',
            self::Other => 'Outro',
        };
    }
}
