<?php

namespace App\Domain\Fipe;

use App\Enums\VehicleCategory;

enum FipeVehicleType: string
{
    case Cars = 'cars';
    case Motorcycles = 'motorcycles';
    case Trucks = 'trucks';

    /**
     * Carro e moto mapeiam direto; "outro" não tem tipo definido e exige que o usuário escolha.
     */
    public static function forCategory(VehicleCategory $category): ?self
    {
        return match ($category) {
            VehicleCategory::Car => self::Cars,
            VehicleCategory::Motorcycle => self::Motorcycles,
            VehicleCategory::Other => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Cars => 'Carros',
            self::Motorcycles => 'Motos',
            self::Trucks => 'Caminhões',
        };
    }
}
