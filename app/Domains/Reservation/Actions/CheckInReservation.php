<?php

namespace App\Domains\Reservation\Actions;

use App\Domains\Reservation\Enums\PaymentStatus;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Reservation\Models\ReservationStatusHistory;
use App\Domains\Reservation\Models\Stay;
use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Enums\OccupancyStatus;
use App\Domains\Room\Enums\OperationalStatus;
use App\Domains\Room\Models\Room;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInReservation
{
    public function __construct(
        private readonly RoomAvailability $availability,
        private readonly RoomOccupancySynchronizer $occupancySynchronizer,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(Reservation $reservation, int $roomId): Reservation
    {
        return DB::transaction(function () use ($reservation, $roomId): Reservation {
            $reservation = Reservation::query()->with('guest')->lockForUpdate()->findOrFail($reservation->id);
            if ($reservation->reservation_status !== ReservationStatus::Confirmed) {
                throw ValidationException::withMessages(['checkin' => 'Hanya reservasi Confirmed yang dapat check-in.']);
            }
            if ($reservation->payment_status !== PaymentStatus::Paid) {
                throw ValidationException::withMessages(['checkin' => 'Pembayaran kamar wajib lunas sebelum check-in.']);
            }
            if ($reservation->guest->is_blacklisted) {
                throw ValidationException::withMessages(['checkin' => 'Guest masuk blacklist. Hubungi Manager.']);
            }

            $today = today(config('app.timezone'));
            if ($reservation->check_in_date->isAfter($today)) {
                throw ValidationException::withMessages(['checkin' => 'Check-in belum dapat dilakukan sebelum tanggal kedatangan.']);
            }
            if (! $reservation->check_out_date->isAfter($today)) {
                throw ValidationException::withMessages(['checkin' => 'Tanggal reservasi sudah berakhir. Perbarui tanggal terlebih dahulu.']);
            }

            $roomIds = collect([$reservation->room_id, $roomId])->filter()->unique()->sort()->values();
            $rooms = Room::query()->whereKey($roomIds)->lockForUpdate()->get()->keyBy('id');
            $room = $rooms->get($roomId);
            if (! $room || ! $room->is_active || $room->room_type_id !== $reservation->room_type_id) {
                throw ValidationException::withMessages(['roomAssignments' => 'Kamar tidak aktif atau tidak sesuai tipe reservasi.']);
            }
            if ($room->operational_status !== OperationalStatus::Available) {
                throw ValidationException::withMessages(['checkin' => "Room {$room->room_number} sedang maintenance/out of order."]);
            }
            if ($room->housekeeping_status !== HousekeepingStatus::Ready) {
                throw ValidationException::withMessages(['checkin' => "Room {$room->room_number} belum berstatus READY."]);
            }
            if ($room->occupancy_status === OccupancyStatus::Occupied) {
                throw ValidationException::withMessages(['checkin' => "Room {$room->room_number} masih occupied."]);
            }

            $conflict = $this->availability->findConflict(
                $room->id,
                $reservation->check_in_date->toDateString(),
                $reservation->check_out_date->toDateString(),
                $reservation->id,
            );
            if ($conflict) {
                throw ValidationException::withMessages([
                    'checkin' => "Room {$room->room_number} tidak tersedia karena reservasi {$conflict->reservation_number}.",
                ]);
            }

            $previousRoomId = $reservation->room_id;
            $checkedInAt = now();
            $reservation->update([
                'room_id' => $room->id,
                'reservation_status' => ReservationStatus::CheckedIn,
                'checked_in_at' => $checkedInAt,
                'checked_in_by' => auth()->id(),
            ]);

            Stay::query()->create([
                'reservation_id' => $reservation->id,
                'guest_id' => $reservation->guest_id,
                'room_id' => $room->id,
                'checked_in_at' => $checkedInAt,
                'checked_in_by' => auth()->id(),
            ]);

            ReservationStatusHistory::query()->create([
                'reservation_id' => $reservation->id,
                'from_status' => ReservationStatus::Confirmed,
                'to_status' => ReservationStatus::CheckedIn,
                'reason' => 'Guest checked in',
                'changed_by' => auth()->id(),
            ]);

            if ($previousRoomId && $previousRoomId !== $room->id) {
                $this->occupancySynchronizer->execute($rooms->get($previousRoomId));
            }
            $room->update(['occupancy_status' => OccupancyStatus::Occupied]);

            $this->auditLogger->log('checked_in', 'reservation', $reservation, [
                'reservation_status' => ReservationStatus::Confirmed->value,
                'room_id' => $previousRoomId,
            ], [
                'reservation_status' => ReservationStatus::CheckedIn->value,
                'room_id' => $room->id,
                'checked_in_at' => $checkedInAt->toIso8601String(),
            ]);

            return $reservation->refresh()->load(['guest', 'room', 'stay']);
        }, 3);
    }
}
