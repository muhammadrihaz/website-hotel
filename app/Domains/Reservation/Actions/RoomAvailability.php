<?php

namespace App\Domains\Reservation\Actions;

use App\Domains\Reservation\Models\Reservation;

class RoomAvailability
{
    public function findConflict(int $roomId, string $checkIn, string $checkOut, ?int $exceptReservationId = null): ?Reservation
    {
        return Reservation::query()
            ->select(['id', 'reservation_number', 'room_id', 'check_in_date', 'check_out_date', 'reservation_status'])
            ->where('room_id', $roomId)
            ->blocking()
            ->overlapping($checkIn, $checkOut)
            ->when($exceptReservationId, fn ($query) => $query->whereKeyNot($exceptReservationId))
            ->orderBy('check_in_date')
            ->first();
    }
}
