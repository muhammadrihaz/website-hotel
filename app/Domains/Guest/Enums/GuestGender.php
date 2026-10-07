<?php

namespace App\Domains\Guest\Enums;

enum GuestGender: string
{
    case Male = 'MALE';
    case Female = 'FEMALE';
    case Other = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Laki-laki',
            self::Female => 'Perempuan',
            self::Other => 'Lainnya',
        };
    }
}
