<?php

namespace App\Domains\Guest\Enums;

enum IdentityType: string
{
    case Ktp = 'KTP';
    case Passport = 'PASSPORT';
    case Sim = 'SIM';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Ktp => 'KTP',
            self::Passport => 'Paspor',
            self::Sim => 'SIM',
            self::Other => 'Lainnya',
        };
    }
}
