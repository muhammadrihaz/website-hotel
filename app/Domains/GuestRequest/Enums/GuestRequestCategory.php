<?php

namespace App\Domains\GuestRequest\Enums;

enum GuestRequestCategory: string
{
    case ExtraTowel = 'EXTRA_TOWEL';
    case ExtraPillow = 'EXTRA_PILLOW';
    case RoomCleaning = 'ROOM_CLEANING';
    case AcComplaint = 'AC_COMPLAINT';
    case ShowerComplaint = 'SHOWER_COMPLAINT';
    case WifiComplaint = 'WIFI_COMPLAINT';
    case LateCheckout = 'LATE_CHECKOUT';
    case Other = 'OTHER';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
