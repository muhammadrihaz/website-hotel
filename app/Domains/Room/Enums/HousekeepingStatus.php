<?php

namespace App\Domains\Room\Enums;

enum HousekeepingStatus: string
{
    case Dirty = 'DIRTY';
    case Cleaning = 'CLEANING';
    case Clean = 'CLEAN';
    case Ready = 'READY';

    public function label(): string
    {
        return match ($this) {
            self::Dirty => 'Dirty',
            self::Cleaning => 'Cleaning',
            self::Clean => 'Clean',
            self::Ready => 'Ready',
        };
    }
}
