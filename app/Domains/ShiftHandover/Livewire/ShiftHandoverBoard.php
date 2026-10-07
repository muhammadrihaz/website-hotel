<?php

namespace App\Domains\ShiftHandover\Livewire;

use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Models\Room;
use App\Domains\ShiftHandover\Actions\ManageShiftHandover;
use App\Domains\ShiftHandover\Enums\HandoverItemPriority;
use App\Domains\ShiftHandover\Enums\HandoverItemStatus;
use App\Domains\ShiftHandover\Enums\ShiftHandoverStatus;
use App\Domains\ShiftHandover\Enums\ShiftType;
use App\Domains\ShiftHandover\Models\ShiftHandover;
use App\Domains\ShiftHandover\Models\ShiftHandoverItem;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftHandoverBoard extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $shiftDate = '';

    public string $shiftType = 'MORNING';

    public string $handoverTo = '';

    public string $notes = '';

    public ?int $activeHandoverId = null;

    public string $itemRoomId = '';

    public string $itemReservationId = '';

    public string $itemCategory = 'ROOM';

    public string $itemDescription = '';

    public string $itemPriority = 'NORMAL';

    /** @var array<int, string> */
    public array $recipients = [];

    /** @var list<string> */
    public array $categories = ['ROOM', 'RESERVATION', 'PAYMENT', 'GUEST', 'HOUSEKEEPING', 'MAINTENANCE', 'OTHER'];

    public function mount(): void
    {
        $this->shiftDate = today(config('app.timezone'))->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function createHandover(ManageShiftHandover $action): void
    {
        Gate::authorize('shift_handover.create');
        $validated = $this->validate([
            'shiftDate' => ['required', 'date'],
            'shiftType' => ['required', Rule::enum(ShiftType::class)],
            'handoverTo' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $handover = $action->create([
            'shift_date' => $validated['shiftDate'],
            'shift_type' => $validated['shiftType'],
            'handover_to' => filled($validated['handoverTo']) ? (int) $validated['handoverTo'] : null,
            'notes' => $validated['notes'],
        ]);
        $this->activeHandoverId = $handover->id;
        $this->reset(['handoverTo', 'notes']);
        session()->flash('status', 'Draft shift handover berhasil dibuat. Tambahkan item tindak lanjut.');
    }

    public function selectHandover(int $id): void
    {
        Gate::authorize('shift_handover.view');
        $this->activeHandoverId = $id;
        $this->resetValidation();
    }

    public function addItem(ManageShiftHandover $action): void
    {
        Gate::authorize('shift_handover.update');
        $handover = ShiftHandover::query()->findOrFail($this->activeHandoverId);
        $validated = $this->validate([
            'itemRoomId' => ['nullable', 'integer', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
            'itemReservationId' => ['nullable', 'integer', Rule::exists('reservations', 'id')],
            'itemCategory' => ['required', Rule::in($this->categories)],
            'itemDescription' => ['required', 'string', 'min:3', 'max:3000'],
            'itemPriority' => ['required', Rule::enum(HandoverItemPriority::class)],
        ]);
        $action->addItem($handover, [
            'room_id' => filled($validated['itemRoomId']) ? (int) $validated['itemRoomId'] : null,
            'reservation_id' => filled($validated['itemReservationId']) ? (int) $validated['itemReservationId'] : null,
            'category' => $validated['itemCategory'],
            'description' => $validated['itemDescription'],
            'priority' => $validated['itemPriority'],
        ]);
        $this->reset(['itemRoomId', 'itemReservationId', 'itemDescription']);
        $this->itemCategory = 'ROOM';
        $this->itemPriority = HandoverItemPriority::Normal->value;
        session()->flash('status', 'Item handover berhasil ditambahkan.');
    }

    public function setItemStatus(int $id, string $status, ManageShiftHandover $action): void
    {
        Gate::authorize('shift_handover.update');
        $target = HandoverItemStatus::from($status);
        $action->updateItemStatus(ShiftHandoverItem::query()->findOrFail($id), $target, (int) auth()->id());
        session()->flash('status', "Status item handover diperbarui menjadi {$target->label()}.");
    }

    public function sendHandover(int $id, ManageShiftHandover $action): void
    {
        Gate::authorize('shift_handover.update');
        $recipient = (int) ($this->recipients[$id] ?? 0);
        if (! $recipient) {
            $this->addError('handover', 'Pilih penerima shift terlebih dahulu.');

            return;
        }
        $action->handover(ShiftHandover::query()->findOrFail($id), $recipient);
        session()->flash('status', 'Shift handover dikirim ke penerima.');
    }

    public function acknowledge(int $id, ManageShiftHandover $action): void
    {
        Gate::authorize('shift_handover.update');
        $action->acknowledge(ShiftHandover::query()->findOrFail($id));
        session()->flash('status', 'Shift handover sudah diterima. Item unfinished tetap terlihat sampai selesai.');
    }

    public function render(): View
    {
        $handovers = ShiftHandover::query()
            ->with(['creator:id,name', 'recipient:id,name', 'items.room:id,room_number', 'items.reservation:id,reservation_number'])
            ->withCount(['items', 'items as open_items_count' => fn ($query) => $query->where('status', '!=', HandoverItemStatus::Completed->value)])
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('notes', 'like', "%{$this->search}%")
                    ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('description', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('shift_date')
            ->orderByDesc('id')
            ->paginate(8);

        $activeHandover = $this->activeHandoverId
            ? ShiftHandover::query()->with(['items.room:id,room_number', 'items.reservation:id,reservation_number'])->find($this->activeHandoverId)
            : null;

        return view('livewire.shift-handover.shift-handover-board', [
            'handovers' => $handovers,
            'activeHandover' => $activeHandover,
            'shiftTypes' => ShiftType::cases(),
            'statuses' => ShiftHandoverStatus::cases(),
            'priorities' => HandoverItemPriority::cases(),
            'users' => User::query()->permission('shift_handover.view')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'rooms' => Room::query()->where('is_active', true)->orderBy('room_number')->get(['id', 'room_number']),
            'reservations' => Reservation::query()->whereIn('reservation_status', ['CONFIRMED', 'CHECKED_IN'])->latest('id')->limit(100)->get(['id', 'reservation_number']),
        ]);
    }
}
