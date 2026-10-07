<?php

namespace App\Domains\Room\Enums;

enum OccupancyStatus: string
{
    case Vacant = 'VACANT';
    case Occupied = 'OCCUPIED';
    case Reserved = 'RESERVED';

    public function label(): string
    {
        return match ($this) {
            self::Vacant => 'Vacant',
            self::Occupied => 'Occupied',
            self::Reserved => 'Reserved',
        };
    }
}
