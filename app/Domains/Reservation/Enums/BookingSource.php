<?php

namespace App\Domains\Reservation\Enums;

enum BookingSource: string
{
    case RedDoorz = 'REDDOORZ';
    case Traveloka = 'TRAVELOKA';
    case Agoda = 'AGODA';
    case BookingCom = 'BOOKING_COM';
    case WalkIn = 'WALK_IN';
    case WhatsApp = 'WHATSAPP';
    case Direct = 'DIRECT';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::RedDoorz => 'RedDoorz',
            self::Traveloka => 'Traveloka',
            self::Agoda => 'Agoda',
            self::BookingCom => 'Booking.com',
            self::WalkIn => 'Walk-In',
            self::WhatsApp => 'WhatsApp',
            self::Direct => 'Direct',
            self::Other => 'Lainnya',
        };
    }
}
