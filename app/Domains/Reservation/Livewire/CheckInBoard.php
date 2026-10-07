<?php

namespace App\Domains\Reservation\Livewire;

use App\Domains\Reservation\Actions\CheckInReservation;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Enums\OccupancyStatus;
use App\Domains\Room\Enums\OperationalStatus;
use App\Domains\Room\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class CheckInBoard extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var array<int, string|int> */
    public array $roomAssignments = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function checkIn(int $id, CheckInReservation $action): void
    {
        Gate::authorize('checkin.execute');
        $this->resetErrorBag(['checkin', 'roomAssignments']);
        $reservation = Reservation::query()->findOrFail($id);
        $roomId = (int) ($this->roomAssignments[$id] ?? $reservation->room_id);
        if ($roomId < 1) {
            throw ValidationException::withMessages(['roomAssignments' => 'Pilih kamar sebelum check-in.']);
        }

        $action->execute($reservation, $roomId);
        unset($this->roomAssignments[$id]);
        session()->flash('status', "Check-in {$reservation->reservation_number} berhasil.");
    }

    public function render(): View
    {
        $today = today(config('app.timezone'))->toDateString();
        $arrivals = Reservation::query()
            ->with(['guest:id,guest_code,full_name,phone', 'room:id,room_number', 'roomType:id,name,code'])
            ->where('reservation_status', ReservationStatus::Confirmed->value)
            ->whereDate('check_in_date', '<=', $today)
            ->whereDate('check_out_date', '>', $today)
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('reservation_number', 'like', "%{$this->search}%")
                    ->orWhereHas('guest', fn ($guestQuery) => $guestQuery->where('full_name', 'like', "%{$this->search}%"));
            }))
            ->orderBy('check_in_date')
            ->paginate(10);

        $rooms = Room::query()
            ->where('is_active', true)
            ->whereIn('room_type_id', $arrivals->getCollection()->pluck('room_type_id')->unique())
            ->where('housekeeping_status', HousekeepingStatus::Ready->value)
            ->where('operational_status', OperationalStatus::Available->value)
            ->where('occupancy_status', '!=', OccupancyStatus::Occupied->value)
            ->orderBy('room_number')
            ->get(['id', 'room_number', 'room_type_id']);

        $arrivalCollection = $arrivals->getCollection();
        $blockers = collect();
        if ($arrivalCollection->isNotEmpty() && $rooms->isNotEmpty()) {
            $blockers = Reservation::query()
                ->select(['id', 'room_id', 'check_in_date', 'check_out_date'])
                ->blocking()
                ->whereIn('room_id', $rooms->pluck('id'))
                ->whereDate('check_in_date', '<', $arrivalCollection->max('check_out_date')->toDateString())
                ->whereDate('check_out_date', '>', $arrivalCollection->min('check_in_date')->toDateString())
                ->get();
        }

        $availableRooms = [];
        foreach ($arrivals as $arrival) {
            $blockedRoomIds = $blockers
                ->filter(fn (Reservation $blocker): bool => $blocker->id !== $arrival->id
                    && $blocker->check_in_date->lt($arrival->check_out_date)
                    && $blocker->check_out_date->gt($arrival->check_in_date))
                ->pluck('room_id');
            $availableRooms[$arrival->id] = $rooms
                ->where('room_type_id', $arrival->room_type_id)
                ->whereNotIn('id', $blockedRoomIds)
                ->values();
        }

        return view('livewire.reservations.check-in-board', [
            'arrivals' => $arrivals,
            'availableRooms' => $availableRooms,
            'today' => $today,
        ]);
    }
}
