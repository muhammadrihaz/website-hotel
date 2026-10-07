<?php

namespace App\Domains\GuestRequest\Enums;

enum GuestRequestDepartment: string
{
    case FrontOffice = 'FRONT_OFFICE';
    case Housekeeping = 'HOUSEKEEPING';
    case Maintenance = 'MAINTENANCE';
    case Management = 'MANAGEMENT';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
