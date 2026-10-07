<?php

namespace App\Domains\Maintenance\Enums;

enum MaintenancePriority: string
{
    case Low = 'LOW';
    case Normal = 'NORMAL';
    case High = 'HIGH';
    case Critical = 'CRITICAL';

    public function label(): string
    {
        return ucfirst(strtolower($this->value));
    }
}
