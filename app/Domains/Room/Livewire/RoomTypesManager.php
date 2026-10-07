<?php

namespace App\Domains\Room\Livewire;

use App\Domains\Room\Actions\DeleteRoomType;
use App\Domains\Room\Actions\SaveRoomType;
use App\Domains\Room\Models\RoomType;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class RoomTypesManager extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public ?int $roomTypeId = null;

    public string $name = '';

    public string $code = '';

    public ?string $description = null;

    public string $baseRate = '0';

    public int $capacityAdult = 2;

    public int $capacityChild = 0;

    public bool $isActive = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $roomType = RoomType::query()->findOrFail($id);
        $this->authorize('update', $roomType);

        $this->roomTypeId = $roomType->id;
        $this->name = $roomType->name;
        $this->code = $roomType->code;
        $this->description = $roomType->description;
        $this->baseRate = (string) (int) round((float) $roomType->base_rate);
        $this->capacityAdult = $roomType->capacity_adult;
        $this->capacityChild = $roomType->capacity_child;
        $this->isActive = $roomType->is_active;
        $this->resetValidation();
    }

    public function save(SaveRoomType $action): void
    {
        $roomType = $this->roomTypeId ? RoomType::query()->findOrFail($this->roomTypeId) : null;
        $this->authorize($roomType ? 'update' : 'create', $roomType ?? RoomType::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required', 'alpha_dash', 'max:20',
                Rule::unique('room_types', 'code')->ignore($this->roomTypeId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'baseRate' => ['required', 'integer', 'min:0', 'max:9999999999999'],
            'capacityAdult' => ['required', 'integer', 'min:1', 'max:20'],
            'capacityChild' => ['required', 'integer', 'min:0', 'max:20'],
            'isActive' => ['boolean'],
        ]);

        $action->execute([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'],
            'base_rate' => $validated['baseRate'],
            'capacity_adult' => $validated['capacityAdult'],
            'capacity_child' => $validated['capacityChild'],
            'is_active' => $validated['isActive'],
        ], $roomType);

        session()->flash('status', $roomType ? 'Tipe kamar berhasil diperbarui.' : 'Tipe kamar berhasil dibuat.');
        $this->resetForm();
    }

    public function delete(int $id, DeleteRoomType $action): void
    {
        $roomType = RoomType::query()->findOrFail($id);
        $this->authorize('delete', $roomType);
        $action->execute($roomType);
        session()->flash('status', 'Tipe kamar berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['roomTypeId', 'name', 'code', 'description', 'baseRate', 'capacityAdult', 'capacityChild']);
        $this->baseRate = '0';
        $this->capacityAdult = 2;
        $this->capacityChild = 0;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        $roomTypes = RoomType::query()
            ->withCount('rooms')
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.rooms.room-types-manager', compact('roomTypes'));
    }
}
