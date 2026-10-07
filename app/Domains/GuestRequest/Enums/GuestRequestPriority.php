<?php

namespace App\Domains\GuestRequest\Enums;

enum GuestRequestPriority: string
{
    case Low = 'LOW';
    case Normal = 'NORMAL';
    case High = 'HIGH';
    case Urgent = 'URGENT';

    public function label(): string
    {
        return ucfirst(strtolower($this->value));
    }
}
