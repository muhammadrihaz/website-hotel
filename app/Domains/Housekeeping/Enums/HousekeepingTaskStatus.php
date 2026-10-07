<?php

namespace App\Domains\Housekeeping\Enums;

enum HousekeepingTaskStatus: string
{
    case Pending = 'PENDING';
    case Assigned = 'ASSIGNED';
    case Cleaning = 'CLEANING';
    case Completed = 'COMPLETED';
    case Verified = 'VERIFIED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Assigned => 'Assigned',
            self::Cleaning => 'Cleaning',
            self::Completed => 'Menunggu verifikasi',
            self::Verified => 'Verified',
        };
    }
}
