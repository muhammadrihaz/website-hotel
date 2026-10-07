<?php

namespace App\Domains\Reservation\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'UNPAID';
    case Partial = 'PARTIAL';
    case Paid = 'PAID';
    case Refunded = 'REFUNDED';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum bayar',
            self::Partial => 'Sebagian',
            self::Paid => 'Lunas',
            self::Refunded => 'Refunded',
        };
    }
}
