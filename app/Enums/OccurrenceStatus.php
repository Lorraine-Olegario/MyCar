<?php

namespace App\Enums;

enum OccurrenceStatus: string
{
    case Forecast = 'forecast';
    case Paid = 'paid';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Forecast => 'Previsto',
            self::Paid => 'Pago',
            self::Canceled => 'Cancelado',
        };
    }
}
