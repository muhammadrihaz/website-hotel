<?php

namespace App\Domains\Reservation\Actions;

use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Reservation\Models\Stay;
use App\Domains\Room\Enums\OccupancyStatus;
use App\Domains\Room\Models\Room;

class RoomOccupancySynchronizer
{
    public function execute(Room $room): Room
    {
        $hasActiveStay = Stay::query()
            ->where('room_id', $room->getKey())
            ->whereNull('checked_out_at')
            ->exists();

        $today = today(config('app.timezone'))->toDateString();
        $hasDueReservation = Reservation::query()
            ->where('room_id', $room->getKey())
            ->where('reservation_status', ReservationStatus::Confirmed->value)
            ->whereDate('check_in_date', '<=', $today)
            ->whereDate('check_out_date', '>', $today)
            ->exists();

        $status = match (true) {
            $hasActiveStay => OccupancyStatus::Occupied,
            $hasDueReservation => OccupancyStatus::Reserved,
            default => OccupancyStatus::Vacant,
        };

        if ($room->occupancy_status !== $status) {
            $room->occupancy_status = $status;
            $room->save();
        }

        return $room->refresh();
    }
}
