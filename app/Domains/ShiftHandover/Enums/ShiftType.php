<?php

namespace App\Domains\ShiftHandover\Enums;

enum ShiftType: string
{
    case Morning = 'MORNING';
    case Afternoon = 'AFTERNOON';
    case Night = 'NIGHT';

    public function label(): string
    {
        return ucfirst(strtolower($this->value));
    }
}
