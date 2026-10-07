<?php

namespace App\Domains\Maintenance\Enums;

enum MaintenanceStatus: string
{
    case Open = 'OPEN';
    case Assigned = 'ASSIGNED';
    case InProgress = 'IN_PROGRESS';
    case Waiting = 'WAITING';
    case Resolved = 'RESOLVED';
    case Verified = 'VERIFIED';
    case Closed = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::Waiting => 'Waiting',
            self::Resolved => 'Resolved',
            self::Verified => 'Verified',
            self::Closed => 'Closed',
        };
    }

    /** @return list<self> */
    public static function active(): array
    {
        return [self::Open, self::Assigned, self::InProgress, self::Waiting];
    }

    /** @return list<self> */
    public static function blocking(): array
    {
        return [...self::active(), self::Resolved];
    }
}
