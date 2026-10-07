<?php

namespace App\Domains\Reservation\Enums;

enum ReservationStatus: string
{
    case Pending = 'PENDING';
    case Confirmed = 'CONFIRMED';
    case CheckedIn = 'CHECKED_IN';
    case CheckedOut = 'CHECKED_OUT';
    case Cancelled = 'CANCELLED';
    case NoShow = 'NO_SHOW';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::CheckedIn => 'Checked-In',
            self::CheckedOut => 'Checked-Out',
            self::Cancelled => 'Cancelled',
            self::NoShow => 'No Show',
        };
    }

    /** @return list<self> */
    public static function blocking(): array
    {
        return [self::Pending, self::Confirmed, self::CheckedIn, self::CheckedOut];
    }

    public function canBeEdited(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }
}
