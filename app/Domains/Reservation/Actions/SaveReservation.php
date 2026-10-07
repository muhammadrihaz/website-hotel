<?php

namespace App\Domains\Reservation\Actions;

use App\Domains\Guest\Models\Guest;
use App\Domains\Reservation\Enums\PaymentStatus;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Reservation\Models\ReservationStatusHistory;
use App\Domains\Room\Enums\OperationalStatus;
use App\Domains\Room\Models\Room;
use App\Domains\Room\Models\RoomType;
use App\Domains\System\Actions\AuditLogger;
use App\Domains\System\Actions\NumberGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveReservation
{
    public function __construct(
        private readonly NumberGenerator $numberGenerator,
        private readonly RoomAvailability $availability,
        private readonly RoomOccupancySynchronizer $occupancySynchronizer,
        private readonly MoneyCalculator $money,
        private readonly AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, ?Reservation $reservation = null): Reservation
    {
        return DB::transaction(function () use ($data, $reservation): Reservation {
            $reservation = $reservation?->exists
                ? Reservation::query()->lockForUpdate()->findOrFail($reservation->getKey())
                : new Reservation;

            if ($reservation->exists && ! $reservation->reservation_status->canBeEdited()) {
                throw ValidationException::withMessages(['reservation' => 'Reservasi yang sudah diproses tidak dapat diedit.']);
            }

            $checkIn = CarbonImmutable::parse($data['check_in_date'])->startOfDay();
            $checkOut = CarbonImmutable::parse($data['check_out_date'])->startOfDay();
            if ($checkOut->lessThanOrEqualTo($checkIn)) {
                throw ValidationException::withMessages(['checkOutDate' => 'Tanggal check-out harus setelah check-in.']);
            }

            $guest = Guest::query()->findOrFail($data['guest_id']);
            if ($guest->is_blacklisted) {
                throw ValidationException::withMessages(['guestId' => 'Guest masuk blacklist dan tidak dapat dibuatkan reservasi.']);
            }

            $roomType = RoomType::query()->where('is_active', true)->findOrFail($data['room_type_id']);
            $oldRoomId = $reservation->room_id;
            $newRoomId = filled($data['room_id'] ?? null) ? (int) $data['room_id'] : null;
            $roomIds = collect([$oldRoomId, $newRoomId])->filter()->unique()->sort()->values();
            $lockedRooms = Room::query()->whereKey($roomIds)->lockForUpdate()->get()->keyBy('id');
            $room = $newRoomId ? $lockedRooms->get($newRoomId) : null;

            if ($newRoomId && ! $room) {
                throw ValidationException::withMessages(['roomId' => 'Kamar tidak ditemukan.']);
            }
            if ($room && (! $room->is_active || $room->room_type_id !== $roomType->id)) {
                throw ValidationException::withMessages(['roomId' => 'Kamar tidak aktif atau tidak sesuai tipe yang dipilih.']);
            }
            if ($room && $room->operational_status !== OperationalStatus::Available) {
                throw ValidationException::withMessages(['roomId' => "Room {$room->room_number} sedang tidak tersedia secara operasional."]);
            }

            if ($room) {
                $conflict = $this->availability->findConflict($room->id, $checkIn->toDateString(), $checkOut->toDateString(), $reservation->id);
                if ($conflict) {
                    throw ValidationException::withMessages([
                        'roomId' => "Room {$room->room_number} tidak tersedia pada tanggal tersebut karena sudah memiliki reservasi {$conflict->reservation_number}.",
                    ]);
                }
            }

            $status = ReservationStatus::from($data['reservation_status']);
            if (! in_array($status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)) {
                throw ValidationException::withMessages(['reservationStatus' => 'Status hanya dapat Pending atau Confirmed saat reservasi disimpan.']);
            }

            $rateMinor = $this->money->toMinor($data['room_rate'], 'roomRate');
            $additionalMinor = $this->money->toMinor($data['additional_charge'] ?? 0, 'additionalCharge');
            $discountMinor = $this->money->toMinor($data['discount'] ?? 0, 'discount');
            $paidMinor = $this->money->toMinor($data['paid_amount'] ?? 0, 'paidAmount');
            $securityDepositMinor = $this->money->toMinor($data['security_deposit_amount'] ?? 0, 'securityDepositAmount');
            $roomTotalMinor = $rateMinor * $checkIn->diffInDays($checkOut);
            $grandTotalMinor = $roomTotalMinor + $additionalMinor - $discountMinor;

            if ($grandTotalMinor < 0) {
                throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh melebihi total biaya.']);
            }
            if ($paidMinor > $grandTotalMinor) {
                throw ValidationException::withMessages(['paidAmount' => 'Pembayaran kamar tidak boleh melebihi total tagihan.']);
            }
            if ($status === ReservationStatus::Confirmed && $paidMinor < $grandTotalMinor) {
                throw ValidationException::withMessages([
                    'paidAmount' => 'Reservasi Confirmed wajib lunas. Pembayaran kamar yang diperlukan Rp'.number_format($grandTotalMinor / 100, 0, ',', '.'),
                ]);
            }

            $paymentStatus = match (true) {
                $grandTotalMinor > 0 && $paidMinor >= $grandTotalMinor => PaymentStatus::Paid,
                $paidMinor > 0 => PaymentStatus::Partial,
                default => PaymentStatus::Unpaid,
            };

            $oldValues = $reservation->exists ? $reservation->only($this->auditedFields()) : [];
            $oldStatus = $reservation->exists ? $reservation->reservation_status : null;
            if (! $reservation->exists) {
                $reservation->reservation_number = $this->numberGenerator->generate('RES');
                $reservation->created_by = auth()->id();
            }

            $reservation->fill([
                ...$data,
                'room_id' => $newRoomId,
                'check_in_date' => $checkIn->toDateString(),
                'check_out_date' => $checkOut->toDateString(),
                'total_room_amount' => $this->money->fromMinor($roomTotalMinor),
                'additional_charge' => $this->money->fromMinor($additionalMinor),
                'discount' => $this->money->fromMinor($discountMinor),
                'paid_amount' => $this->money->fromMinor($paidMinor),
                'security_deposit_amount' => $this->money->fromMinor($securityDepositMinor),
                'total_amount' => $this->money->fromMinor($grandTotalMinor),
                'payment_status' => $paymentStatus,
                'reservation_status' => $status,
            ])->save();

            if ($oldStatus !== $status) {
                ReservationStatusHistory::query()->create([
                    'reservation_id' => $reservation->id,
                    'from_status' => $oldStatus,
                    'to_status' => $status,
                    'reason' => $oldStatus ? 'Reservation updated' : 'Reservation created',
                    'changed_by' => auth()->id(),
                ]);
            }

            foreach ($roomIds as $roomId) {
                $this->occupancySynchronizer->execute($lockedRooms->get($roomId));
            }

            $this->auditLogger->log(
                $oldValues === [] ? 'created' : 'updated',
                'reservation',
                $reservation,
                $oldValues,
                $reservation->only($this->auditedFields()),
            );

            return $reservation->refresh();
        }, 3);
    }

    /** @return list<string> */
    private function auditedFields(): array
    {
        return [
            'reservation_number', 'guest_id', 'room_type_id', 'room_id', 'booking_source',
            'booking_reference', 'check_in_date', 'check_out_date', 'adult_count', 'child_count',
            'room_rate', 'total_room_amount', 'additional_charge', 'discount', 'paid_amount',
            'security_deposit_amount',
            'total_amount', 'payment_status', 'reservation_status',
        ];
    }
}
