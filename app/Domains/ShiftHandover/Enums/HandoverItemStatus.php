<?php

namespace App\Domains\ShiftHandover\Enums;

enum HandoverItemStatus: string
{
    case Open = 'OPEN';
    case InProgress = 'IN_PROGRESS';
    case Completed = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
        };
    }
}
