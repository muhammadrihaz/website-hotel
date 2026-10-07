<?php

namespace App\Domains\Reservation\Actions;

use App\Domains\Housekeeping\Actions\CreateHousekeepingTask;
use App\Domains\Housekeeping\Enums\HousekeepingPriority;
use App\Domains\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Reservation\Models\ReservationStatusHistory;
use App\Domains\Reservation\Models\Stay;
use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Enums\OccupancyStatus;
use App\Domains\Room\Models\Room;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckOutReservation
{
    public function __construct(
        private readonly RoomOccupancySynchronizer $occupancySynchronizer,
        private readonly CreateHousekeepingTask $createHousekeepingTask,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(Reservation $reservation, bool $folioReviewed): Reservation
    {
        return DB::transaction(function () use ($reservation, $folioReviewed): Reservation {
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            if ($reservation->reservation_status !== ReservationStatus::CheckedIn) {
                throw ValidationException::withMessages(['checkout' => 'Hanya reservasi Checked-In yang dapat check-out.']);
            }
            if (! $folioReviewed) {
                throw ValidationException::withMessages(['folioReviewed' => 'Konfirmasi bahwa folio dan status pembayaran sudah diperiksa.']);
            }

            $room = Room::query()->lockForUpdate()->findOrFail($reservation->room_id);
            $stay = Stay::query()
                ->where('reservation_id', $reservation->id)
                ->whereNull('checked_out_at')
                ->lockForUpdate()
                ->firstOrFail();
            $checkedOutAt = now();

            $reservation->update([
                'reservation_status' => ReservationStatus::CheckedOut,
                'checked_out_at' => $checkedOutAt,
                'checked_out_by' => auth()->id(),
            ]);
            $stay->update([
                'checked_out_at' => $checkedOutAt,
                'checked_out_by' => auth()->id(),
            ]);
            $room->update([
                'occupancy_status' => OccupancyStatus::Vacant,
                'housekeeping_status' => HousekeepingStatus::Dirty,
            ]);

            $this->createHousekeepingTask->execute(
                $room,
                $reservation->id,
                HousekeepingPriority::Normal,
                "Auto-created after checkout {$reservation->reservation_number}",
            );

            ReservationStatusHistory::query()->create([
                'reservation_id' => $reservation->id,
                'from_status' => ReservationStatus::CheckedIn,
                'to_status' => ReservationStatus::CheckedOut,
                'reason' => 'Guest checked out; folio reviewed',
                'changed_by' => auth()->id(),
            ]);

            $this->occupancySynchronizer->execute($room);
            $this->auditLogger->log('checked_out', 'reservation', $reservation, [
                'reservation_status' => ReservationStatus::CheckedIn->value,
            ], [
                'reservation_status' => ReservationStatus::CheckedOut->value,
                'checked_out_at' => $checkedOutAt->toIso8601String(),
                'folio_reviewed' => true,
                'housekeeping_task' => HousekeepingTaskStatus::Pending->value,
            ]);

            return $reservation->refresh()->load(['guest', 'room', 'stay']);
        }, 3);
    }
}
