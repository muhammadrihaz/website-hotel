<?php

namespace App\Domains\Room\Livewire;

use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Enums\OccupancyStatus;
use App\Domains\Room\Enums\OperationalStatus;
use App\Domains\Room\Models\Room;
use App\Domains\Room\Models\RoomType;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class RoomBoard extends Component
{
    use AuthorizesRequests;

    public string $search = '';

    public string $statusFilter = '';

    public string $floorFilter = '';

    public string $typeFilter = '';

    public ?int $selectedRoomId = null;

    public function showRoom(int $id): void
    {
        $room = Room::query()->findOrFail($id);
        $this->authorize('view', $room);
        $this->selectedRoomId = $room->id;
    }

    public function closeRoom(): void
    {
        $this->selectedRoomId = null;
    }

    public function render(): View
    {
        $rooms = Room::query()
            ->with(['roomType:id,name,code', 'activeStay.guest:id,full_name', 'activeStay.reservation:id,reservation_number,booking_source,check_in_date,check_out_date,payment_status'])
            ->where('is_active', true)
            ->when($this->search, fn ($query) => $query->where('room_number', 'like', "%{$this->search}%"))
            ->when($this->floorFilter !== '', fn ($query) => $query->where('floor', $this->floorFilter))
            ->when($this->typeFilter !== '', fn ($query) => $query->where('room_type_id', $this->typeFilter))
            ->when($this->statusFilter !== '', function ($query): void {
                if (collect(OccupancyStatus::cases())->pluck('value')->contains($this->statusFilter)) {
                    $query->where('occupancy_status', $this->statusFilter);
                } elseif (collect(HousekeepingStatus::cases())->pluck('value')->contains($this->statusFilter)) {
                    $query->where('housekeeping_status', $this->statusFilter);
                } elseif (collect(OperationalStatus::cases())->pluck('value')->contains($this->statusFilter)) {
                    $query->where('operational_status', $this->statusFilter);
                }
            })
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();

        $selectedRoom = $this->selectedRoomId
            ? Room::query()->with([
                'roomType:id,name,code',
                'activeStay.guest:id,guest_code,full_name,phone',
                'activeStay.reservation',
                'reservations' => fn ($query) => $query->with('guest:id,full_name')->whereIn('reservation_status', ['PENDING', 'CONFIRMED', 'CHECKED_IN'])->orderBy('check_in_date')->limit(8),
                'housekeepingTasks' => fn ($query) => $query->latest()->limit(3),
            ])->findOrFail($this->selectedRoomId)
            : null;

        return view('livewire.rooms.room-board', [
            'rooms' => $rooms,
            'selectedRoom' => $selectedRoom,
            'roomTypes' => RoomType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'floors' => Room::query()->where('is_active', true)->distinct()->orderBy('floor')->pluck('floor'),
        ]);
    }
}
