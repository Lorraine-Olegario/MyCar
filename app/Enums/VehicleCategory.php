<?php

namespace App\Enums;

enum VehicleCategory: string
{
    case Car = 'car';
    case Motorcycle = 'motorcycle';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Car => 'Carro',
            self::Motorcycle => 'Moto',
            self::Other => 'Outro',
        };
    }
}
