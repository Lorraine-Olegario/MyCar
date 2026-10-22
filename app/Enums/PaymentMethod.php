<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Debit = 'debit';
    case Credit = 'credit';
    case Pix = 'pix';
    case Transfer = 'transfer';
    case Boleto = 'boleto';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Dinheiro',
            self::Debit => 'Cartão de débito',
            self::Credit => 'Cartão de crédito',
            self::Pix => 'Pix',
            self::Transfer => 'Transferência',
            self::Boleto => 'Boleto',
            self::Other => 'Outro',
        };
    }
}
