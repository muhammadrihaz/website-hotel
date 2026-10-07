<?php

namespace App\Domains\Room\Enums;

enum OperationalStatus: string
{
    case Available = 'AVAILABLE';
    case Maintenance = 'MAINTENANCE';
    case OutOfOrder = 'OUT_OF_ORDER';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Maintenance => 'Maintenance',
            self::OutOfOrder => 'Out of order',
        };
    }
}
