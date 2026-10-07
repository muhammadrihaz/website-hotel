<?php

namespace App\Domains\ShiftHandover\Enums;

enum ShiftHandoverStatus: string
{
    case Draft = 'DRAFT';
    case HandedOver = 'HANDED_OVER';
    case Acknowledged = 'ACKNOWLEDGED';
    case Closed = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::HandedOver => 'Menunggu penerimaan',
            self::Acknowledged => 'Diterima',
            self::Closed => 'Closed',
        };
    }
}
