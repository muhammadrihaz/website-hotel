<?php

namespace App\Domains\Reservation\Actions;

use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Reservation\Models\ReservationStatusHistory;
use App\Domains\Room\Models\Room;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelReservation
{
    public function __construct(
        private readonly RoomOccupancySynchronizer $occupancySynchronizer,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(Reservation $reservation, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservation, $reason): Reservation {
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            if (! $reservation->reservation_status->canBeEdited()) {
                throw ValidationException::withMessages(['reservation' => 'Hanya reservasi Pending/Confirmed yang dapat dibatalkan.']);
            }
            if (mb_strlen(trim($reason)) < 3) {
                throw ValidationException::withMessages(['cancellationReason' => 'Alasan pembatalan minimal 3 karakter.']);
            }

            $room = $reservation->room_id
                ? Room::query()->lockForUpdate()->findOrFail($reservation->room_id)
                : null;
            $oldStatus = $reservation->reservation_status;
            $reservation->update([
                'reservation_status' => ReservationStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => trim($reason),
            ]);

            ReservationStatusHistory::query()->create([
                'reservation_id' => $reservation->id,
                'from_status' => $oldStatus,
                'to_status' => ReservationStatus::Cancelled,
                'reason' => trim($reason),
                'changed_by' => auth()->id(),
            ]);

            if ($room) {
                $this->occupancySynchronizer->execute($room);
            }

            $this->auditLogger->log('cancelled', 'reservation', $reservation, [
                'reservation_status' => $oldStatus->value,
            ], [
                'reservation_status' => ReservationStatus::Cancelled->value,
                'reason' => trim($reason),
            ]);

            return $reservation->refresh();
        }, 3);
    }
}
