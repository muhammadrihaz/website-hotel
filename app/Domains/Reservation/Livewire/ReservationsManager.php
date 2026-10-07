<?php

namespace App\Domains\Reservation\Livewire;

use App\Domains\Guest\Models\Guest;
use App\Domains\Reservation\Actions\CancelReservation;
use App\Domains\Reservation\Actions\RoomAvailability;
use App\Domains\Reservation\Actions\SaveReservation;
use App\Domains\Reservation\Enums\BookingSource;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Models\Room;
use App\Domains\Room\Models\RoomType;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ReservationsManager extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $sourceFilter = '';

    public ?int $reservationId = null;

    public string $guestId = '';

    public string $roomTypeId = '';

    public string $roomId = '';

    public string $bookingSource = 'DIRECT';

    public string $bookingReference = '';

    public string $checkInDate = '';

    public string $checkOutDate = '';

    public int $adultCount = 1;

    public int $childCount = 0;

    public string $roomRate = '0';

    public string $additionalCharge = '0';

    public string $discount = '0';

    public string $paidAmount = '0';

    public string $securityDepositAmount = '100000';

    public string $reservationStatus = 'PENDING';

    public string $specialRequest = '';

    public string $internalNote = '';

    public string $availabilityMessage = '';

    /** @var array<int, string> */
    public array $cancellationReasons = [];

    public function mount(): void
    {
        $this->setDefaultDates();
        $this->securityDepositAmount = (string) config('hotel.reservation.default_security_deposit', 100000);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatedRoomTypeId(): void
    {
        $this->roomId = '';
        $type = filled($this->roomTypeId) ? RoomType::query()->find($this->roomTypeId) : null;
        if ($type) {
            $this->roomRate = $this->wholeRupiah($type->base_rate);
        }
        $this->syncFullPayment();
        $this->availabilityMessage = '';
    }

    public function updatedRoomId(): void
    {
        $this->checkAvailability(app(RoomAvailability::class));
    }

    public function updatedCheckInDate(): void
    {
        $this->checkAvailability(app(RoomAvailability::class));
        $this->syncFullPayment();
    }

    public function updatedCheckOutDate(): void
    {
        $this->checkAvailability(app(RoomAvailability::class));
        $this->syncFullPayment();
    }

    public function updatedRoomRate(): void
    {
        $this->syncFullPayment();
    }

    public function updatedAdditionalCharge(): void
    {
        $this->syncFullPayment();
    }

    public function updatedDiscount(): void
    {
        $this->syncFullPayment();
    }

    public function updatedReservationStatus(): void
    {
        if ($this->reservationStatus === ReservationStatus::Confirmed->value) {
            $this->syncFullPayment();
        }
    }

    public function edit(int $id): void
    {
        $reservation = Reservation::query()->findOrFail($id);
        $this->authorize('update', $reservation);

        $this->reservationId = $reservation->id;
        $this->guestId = (string) $reservation->guest_id;
        $this->roomTypeId = (string) $reservation->room_type_id;
        $this->roomId = $reservation->room_id ? (string) $reservation->room_id : '';
        $this->bookingSource = $reservation->booking_source->value;
        $this->bookingReference = $reservation->booking_reference ?? '';
        $this->checkInDate = $reservation->check_in_date->format('Y-m-d');
        $this->checkOutDate = $reservation->check_out_date->format('Y-m-d');
        $this->adultCount = $reservation->adult_count;
        $this->childCount = $reservation->child_count;
        $this->roomRate = $this->wholeRupiah($reservation->room_rate);
        $this->additionalCharge = $this->wholeRupiah($reservation->additional_charge);
        $this->discount = $this->wholeRupiah($reservation->discount);
        $this->paidAmount = $this->wholeRupiah($reservation->paid_amount);
        $this->securityDepositAmount = $this->wholeRupiah($reservation->security_deposit_amount);
        $this->reservationStatus = $reservation->reservation_status->value;
        $this->specialRequest = $reservation->special_request ?? '';
        $this->internalNote = $reservation->internal_note ?? '';
        $this->availabilityMessage = '';
        $this->resetValidation();
    }

    public function save(SaveReservation $action): void
    {
        $reservation = $this->reservationId ? Reservation::query()->findOrFail($this->reservationId) : null;
        $this->authorize($reservation ? 'update' : 'create', $reservation ?? Reservation::class);

        $validated = $this->validate([
            'guestId' => ['required', 'integer', Rule::exists('guests', 'id')->whereNull('deleted_at')],
            'roomTypeId' => ['required', 'integer', Rule::exists('room_types', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'roomId' => ['nullable', 'integer', Rule::exists('rooms', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'bookingSource' => ['required', Rule::enum(BookingSource::class)],
            'bookingReference' => ['nullable', 'string', 'max:100'],
            'checkInDate' => ['required', 'date'],
            'checkOutDate' => ['required', 'date', 'after:checkInDate'],
            'adultCount' => ['required', 'integer', 'min:1', 'max:20'],
            'childCount' => ['required', 'integer', 'min:0', 'max:20'],
            'roomRate' => ['required', 'integer', 'min:0', 'max:9999999999999'],
            'additionalCharge' => ['required', 'integer', 'min:0', 'max:9999999999999'],
            'discount' => ['required', 'integer', 'min:0', 'max:9999999999999'],
            'paidAmount' => ['required', 'integer', 'min:0', 'max:9999999999999'],
            'securityDepositAmount' => ['required', 'integer', 'min:0', 'max:9999999999999'],
            'reservationStatus' => ['required', Rule::in([ReservationStatus::Pending->value, ReservationStatus::Confirmed->value])],
            'specialRequest' => ['nullable', 'string', 'max:3000'],
            'internalNote' => ['nullable', 'string', 'max:3000'],
        ]);

        $action->execute([
            'guest_id' => (int) $validated['guestId'],
            'room_type_id' => (int) $validated['roomTypeId'],
            'room_id' => filled($validated['roomId']) ? (int) $validated['roomId'] : null,
            'booking_source' => $validated['bookingSource'],
            'booking_reference' => filled($validated['bookingReference']) ? trim($validated['bookingReference']) : null,
            'check_in_date' => $validated['checkInDate'],
            'check_out_date' => $validated['checkOutDate'],
            'adult_count' => $validated['adultCount'],
            'child_count' => $validated['childCount'],
            'room_rate' => $validated['roomRate'],
            'additional_charge' => $validated['additionalCharge'],
            'discount' => $validated['discount'],
            'paid_amount' => $validated['paidAmount'],
            'security_deposit_amount' => $validated['securityDepositAmount'],
            'reservation_status' => $validated['reservationStatus'],
            'special_request' => filled($validated['specialRequest']) ? trim($validated['specialRequest']) : null,
            'internal_note' => filled($validated['internalNote']) ? trim($validated['internalNote']) : null,
        ], $reservation);

        session()->flash('status', $reservation ? 'Reservasi berhasil diperbarui.' : 'Reservasi berhasil dibuat.');
        $this->resetForm();
    }

    public function cancel(int $id, CancelReservation $action): void
    {
        $reservation = Reservation::query()->findOrFail($id);
        $this->authorize('cancel', $reservation);
        $reason = $this->cancellationReasons[$id] ?? '';
        $action->execute($reservation, $reason);
        unset($this->cancellationReasons[$id]);
        session()->flash('status', "Reservasi {$reservation->reservation_number} dibatalkan.");
    }

    public function resetForm(): void
    {
        $this->reset([
            'reservationId', 'guestId', 'roomTypeId', 'roomId', 'bookingReference',
            'adultCount', 'childCount', 'roomRate', 'additionalCharge', 'discount',
            'paidAmount', 'securityDepositAmount', 'specialRequest', 'internalNote', 'availabilityMessage',
        ]);
        $this->bookingSource = BookingSource::Direct->value;
        $this->reservationStatus = ReservationStatus::Pending->value;
        $this->adultCount = 1;
        $this->childCount = 0;
        $this->roomRate = '0';
        $this->additionalCharge = '0';
        $this->discount = '0';
        $this->paidAmount = '0';
        $this->securityDepositAmount = (string) config('hotel.reservation.default_security_deposit', 100000);
        $this->setDefaultDates();
        $this->resetValidation();
    }

    public function render(): View
    {
        $reservations = Reservation::query()
            ->with(['guest:id,guest_code,full_name,phone,is_blacklisted', 'room:id,room_number', 'roomType:id,name,code'])
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('reservation_number', 'like', "%{$this->search}%")
                    ->orWhere('booking_reference', 'like', "%{$this->search}%")
                    ->orWhereHas('guest', fn ($guestQuery) => $guestQuery->where('full_name', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($query) => $query->where('reservation_status', $this->statusFilter))
            ->when($this->sourceFilter, fn ($query) => $query->where('booking_source', $this->sourceFilter))
            ->orderByDesc('check_in_date')
            ->orderByDesc('id')
            ->paginate(12);

        $candidateRooms = Room::query()
            ->with('roomType:id,name,code')
            ->where('is_active', true)
            ->where('operational_status', 'AVAILABLE')
            ->when($this->roomTypeId, fn ($query) => $query->where('room_type_id', $this->roomTypeId))
            ->orderBy('room_number')
            ->get();

        $unavailableRoomIds = [];
        if ($this->validDateRange()) {
            $unavailableRoomIds = Reservation::query()
                ->blocking()
                ->overlapping($this->checkInDate, $this->checkOutDate)
                ->when($this->reservationId, fn ($query) => $query->whereKeyNot($this->reservationId))
                ->whereNotNull('room_id')
                ->pluck('room_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->all();
        }

        return view('livewire.reservations.reservations-manager', [
            'reservations' => $reservations,
            'guests' => Guest::query()->where('is_blacklisted', false)->orderBy('full_name')->get(['id', 'guest_code', 'full_name']),
            'roomTypes' => RoomType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'base_rate']),
            'candidateRooms' => $candidateRooms,
            'unavailableRoomIds' => $unavailableRoomIds,
            'bookingSources' => BookingSource::cases(),
            'reservationStatuses' => ReservationStatus::cases(),
        ]);
    }

    private function checkAvailability(RoomAvailability $availability): void
    {
        $this->availabilityMessage = '';
        if (! filled($this->roomId) || ! $this->validDateRange()) {
            return;
        }

        $room = Room::query()->find($this->roomId);
        if (! $room) {
            return;
        }
        $conflict = $availability->findConflict((int) $this->roomId, $this->checkInDate, $this->checkOutDate, $this->reservationId);
        $this->availabilityMessage = $conflict
            ? "Room {$room->room_number} bentrok dengan {$conflict->reservation_number}."
            : "Room {$room->room_number} tersedia untuk periode ini.";
    }

    private function validDateRange(): bool
    {
        return filled($this->checkInDate)
            && filled($this->checkOutDate)
            && strtotime($this->checkOutDate) > strtotime($this->checkInDate);
    }

    private function setDefaultDates(): void
    {
        $this->checkInDate = today(config('app.timezone'))->format('Y-m-d');
        $this->checkOutDate = today(config('app.timezone'))->addDay()->format('Y-m-d');
    }

    public function estimatedRoomTotal(): int
    {
        return $this->rupiahInt($this->roomRate) * $this->nightCount();
    }

    public function estimatedTotal(): int
    {
        return max(
            $this->estimatedRoomTotal() + $this->rupiahInt($this->additionalCharge) - $this->rupiahInt($this->discount),
            0,
        );
    }

    private function syncFullPayment(): void
    {
        $this->paidAmount = (string) $this->estimatedTotal();
    }

    public function nightCount(): int
    {
        if (! $this->validDateRange()) {
            return 0;
        }

        return CarbonImmutable::parse($this->checkInDate)->diffInDays(CarbonImmutable::parse($this->checkOutDate));
    }

    private function rupiahInt(mixed $value): int
    {
        return is_numeric($value) ? max(0, (int) round((float) $value)) : 0;
    }

    private function wholeRupiah(mixed $value): string
    {
        return (string) $this->rupiahInt($value);
    }
}
