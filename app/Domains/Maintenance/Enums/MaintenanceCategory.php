<?php

namespace App\Domains\Maintenance\Enums;

enum MaintenanceCategory: string
{
    case AirConditioner = 'AIR_CONDITIONER';
    case Electrical = 'ELECTRICAL';
    case Plumbing = 'PLUMBING';
    case Shower = 'SHOWER';
    case Tv = 'TV';
    case Wifi = 'WIFI';
    case DoorLock = 'DOOR_LOCK';
    case Furniture = 'FURNITURE';
    case Bathroom = 'BATHROOM';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::AirConditioner => 'Air Conditioner',
            self::Electrical => 'Electrical',
            self::Plumbing => 'Plumbing',
            self::Shower => 'Shower',
            self::Tv => 'TV',
            self::Wifi => 'WiFi',
            self::DoorLock => 'Door Lock',
            self::Furniture => 'Furniture',
            self::Bathroom => 'Bathroom',
            self::Other => 'Other',
        };
    }
}
