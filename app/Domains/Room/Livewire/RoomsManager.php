<?php

namespace App\Domains\Room\Livewire;

use App\Domains\Room\Actions\DeleteRoom;
use App\Domains\Room\Actions\SaveRoom;
use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Enums\OccupancyStatus;
use App\Domains\Room\Enums\OperationalStatus;
use App\Domains\Room\Models\Room;
use App\Domains\Room\Models\RoomType;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class RoomsManager extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $floorFilter = '';

    public string $typeFilter = '';

    public ?int $roomId = null;

    public string $roomNumber = '';

    public int $floor = 1;

    public string $roomTypeId = '';

    public string $occupancyStatus = 'VACANT';

    public string $housekeepingStatus = 'READY';

    public string $operationalStatus = 'AVAILABLE';

    public string $baseRate = '0';

    public int $capacityAdult = 2;

    public int $capacityChild = 0;

    public ?string $description = null;

    public ?string $notes = null;

    public bool $isActive = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFloorFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $room = Room::query()->findOrFail($id);
        $this->authorize('update', $room);

        $this->roomId = $room->id;
        $this->roomNumber = $room->room_number;
        $this->floor = $room->floor;
        $this->roomTypeId = (string) $room->room_type_id;
        $this->occupancyStatus = $room->occupancy_status->value;
        $this->housekeepingStatus = $room->housekeeping_status->value;
        $this->operationalStatus = $room->operational_status->value;
        $this->baseRate = (string) (int) round((float) $room->base_rate);
        $this->capacityAdult = $room->capacity_adult;
        $this->capacityChild = $room->capacity_child;
        $this->description = $room->description;
        $this->notes = $room->notes;
        $this->isActive = $room->is_active;
        $this->resetValidation();
    }

    public function save(SaveRoom $action): void
    {
        $room = $this->roomId ? Room::query()->findOrFail($this->roomId) : null;
        $this->authorize($room ? 'update' : 'create', $room ?? Room::class);

        $validated = $this->validate([
            'roomNumber' => [
                'required', 'string', 'max:20',
                Rule::unique('rooms', 'room_number')->ignore($this->roomId),
            ],
            'floor' => ['required', 'integer', 'min:0', 'max:200'],
            'roomTypeId' => ['required', 'integer', Rule::exists('room_types', 'id')->whereNull('deleted_at')],
            'occupancyStatus' => ['required', Rule::enum(OccupancyStatus::class)],
            'housekeepingStatus' => ['required', Rule::enum(HousekeepingStatus::class)],
            'operationalStatus' => ['required', Rule::enum(OperationalStatus::class)],
            'baseRate' => ['required', 'integer', 'min:0', 'max:9999999999999'],
            'capacityAdult' => ['required', 'integer', 'min:1', 'max:20'],
            'capacityChild' => ['required', 'integer', 'min:0', 'max:20'],
            'description' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'isActive' => ['boolean'],
        ]);

        $action->execute([
            'room_number' => trim($validated['roomNumber']),
            'floor' => $validated['floor'],
            'room_type_id' => $validated['roomTypeId'],
            'occupancy_status' => $validated['occupancyStatus'],
            'housekeeping_status' => $validated['housekeepingStatus'],
            'operational_status' => $validated['operationalStatus'],
            'base_rate' => $validated['baseRate'],
            'capacity_adult' => $validated['capacityAdult'],
            'capacity_child' => $validated['capacityChild'],
            'description' => $validated['description'],
            'notes' => $validated['notes'],
            'is_active' => $validated['isActive'],
        ], $room);

        session()->flash('status', $room ? 'Kamar berhasil diperbarui.' : 'Kamar berhasil dibuat.');
        $this->resetForm();
    }

    public function delete(int $id, DeleteRoom $action): void
    {
        $room = Room::query()->findOrFail($id);
        $this->authorize('delete', $room);
        $action->execute($room);
        session()->flash('status', 'Kamar berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset([
            'roomId', 'roomNumber', 'roomTypeId', 'description', 'notes',
            'baseRate', 'capacityAdult', 'capacityChild',
        ]);
        $this->floor = 1;
        $this->occupancyStatus = OccupancyStatus::Vacant->value;
        $this->housekeepingStatus = HousekeepingStatus::Ready->value;
        $this->operationalStatus = OperationalStatus::Available->value;
        $this->baseRate = '0';
        $this->capacityAdult = 2;
        $this->capacityChild = 0;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        $rooms = Room::query()
            ->with('roomType:id,name,code')
            ->when($this->search, fn ($query) => $query->where('room_number', 'like', "%{$this->search}%"))
            ->when($this->floorFilter !== '', fn ($query) => $query->where('floor', $this->floorFilter))
            ->when($this->typeFilter !== '', fn ($query) => $query->where('room_type_id', $this->typeFilter))
            ->orderBy('floor')
            ->orderBy('room_number')
            ->paginate(12);

        return view('livewire.rooms.rooms-manager', [
            'rooms' => $rooms,
            'roomTypes' => RoomType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'base_rate', 'capacity_adult', 'capacity_child']),
            'floors' => Room::query()->distinct()->orderBy('floor')->pluck('floor'),
            'occupancyStatuses' => OccupancyStatus::cases(),
            'housekeepingStatuses' => HousekeepingStatus::cases(),
            'operationalStatuses' => OperationalStatus::cases(),
        ]);
    }
}
