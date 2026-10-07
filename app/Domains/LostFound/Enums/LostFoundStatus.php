<?php

namespace App\Domains\LostFound\Enums;

enum LostFoundStatus: string
{
    case Found = 'FOUND';
    case Stored = 'STORED';
    case Claimed = 'CLAIMED';
    case Returned = 'RETURNED';
    case Disposed = 'DISPOSED';

    public function label(): string
    {
        return ucfirst(strtolower($this->value));
    }
}
