<?php

namespace App\Domains\Housekeeping\Livewire;

use App\Domains\Housekeeping\Actions\CreateHousekeepingTask;
use App\Domains\Housekeeping\Actions\ManageHousekeepingTask;
use App\Domains\Housekeeping\Enums\HousekeepingPriority;
use App\Domains\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domains\Housekeeping\Models\HousekeepingTask;
use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Models\Room;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class HousekeepingBoard extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $floorFilter = '';

    public string $taskRoomId = '';

    public string $priority = 'NORMAL';

    public string $notes = '';

    /** @var array<int, string> */
    public array $assignees = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFloorFilter(): void
    {
        $this->resetPage();
    }

    public function createTask(CreateHousekeepingTask $action): void
    {
        Gate::authorize('housekeeping.update');
        $validated = $this->validate([
            'taskRoomId' => ['required', 'integer', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
            'priority' => ['required', Rule::enum(HousekeepingPriority::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $room = Room::query()->findOrFail($validated['taskRoomId']);
        $action->execute(
            $room,
            null,
            HousekeepingPriority::from($validated['priority']),
            filled($validated['notes']) ? trim($validated['notes']) : 'Manual housekeeping task',
        );
        $this->reset(['taskRoomId', 'notes']);
        $this->priority = HousekeepingPriority::Normal->value;
        session()->flash('status', "Task Room {$room->room_number} berhasil dibuat.");
    }

    public function assign(int $id, ManageHousekeepingTask $action): void
    {
        Gate::authorize('housekeeping.assign');
        $userId = (int) ($this->assignees[$id] ?? 0);
        if (! $userId) {
            $this->addError('housekeeping', 'Pilih petugas housekeeping terlebih dahulu.');

            return;
        }
        $action->assign(HousekeepingTask::query()->findOrFail($id), $userId);
        session()->flash('status', 'Petugas housekeeping berhasil ditetapkan.');
    }

    public function start(int $id, ManageHousekeepingTask $action): void
    {
        Gate::authorize('housekeeping.update');
        $action->start(HousekeepingTask::query()->findOrFail($id), (int) auth()->id());
        session()->flash('status', 'Cleaning dimulai dan status kamar diperbarui.');
    }

    public function toggleChecklist(int $taskId, int $itemId, ManageHousekeepingTask $action): void
    {
        Gate::authorize('housekeeping.update');
        $task = HousekeepingTask::query()->findOrFail($taskId);
        $item = $task->checklistItems()->findOrFail($itemId);
        $isCompleted = ! $item->is_completed;
        $action->toggleChecklist($task, $item->id, $isCompleted, (int) auth()->id());
        session()->flash('status', $isCompleted ? 'Checklist housekeeping ditandai selesai.' : 'Checklist housekeeping dibuka kembali.');
    }

    public function complete(int $id, ManageHousekeepingTask $action): void
    {
        Gate::authorize('housekeeping.update');
        $action->complete(HousekeepingTask::query()->findOrFail($id));
        session()->flash('status', 'Cleaning selesai dan menunggu verifikasi supervisor.');
    }

    public function verify(int $id, ManageHousekeepingTask $action): void
    {
        Gate::authorize('housekeeping.verify');
        $task = $action->verify(HousekeepingTask::query()->findOrFail($id), (int) auth()->id());
        session()->flash('status', "Room {$task->room->room_number} sudah VERIFIED dan READY.");
    }

    public function render(): View
    {
        $tasks = HousekeepingTask::query()
            ->with(['room.roomType:id,name,code', 'reservation:id,reservation_number', 'assignee:id,name', 'verifier:id,name', 'checklistItems'])
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->whereHas('room', fn ($roomQuery) => $roomQuery->where('room_number', 'like', "%{$this->search}%"))
                    ->orWhereHas('reservation', fn ($reservationQuery) => $reservationQuery->where('reservation_number', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->floorFilter, fn ($query) => $query->whereHas('room', fn ($roomQuery) => $roomQuery->where('floor', $this->floorFilter)))
            ->orderByRaw("CASE priority WHEN 'URGENT' THEN 1 WHEN 'HIGH' THEN 2 WHEN 'NORMAL' THEN 3 ELSE 4 END")
            ->orderByDesc('id')
            ->paginate(9);

        $activeStatuses = [
            HousekeepingTaskStatus::Pending->value,
            HousekeepingTaskStatus::Assigned->value,
            HousekeepingTaskStatus::Cleaning->value,
            HousekeepingTaskStatus::Completed->value,
        ];

        return view('livewire.housekeeping.housekeeping-board', [
            'tasks' => $tasks,
            'statuses' => HousekeepingTaskStatus::cases(),
            'priorities' => HousekeepingPriority::cases(),
            'floors' => Room::query()->where('is_active', true)->distinct()->orderBy('floor')->pluck('floor'),
            'staff' => User::query()->permission('housekeeping.update')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'dirtyRooms' => Room::query()
                ->where('is_active', true)
                ->where('housekeeping_status', HousekeepingStatus::Dirty->value)
                ->whereDoesntHave('housekeepingTasks', fn ($query) => $query->whereIn('status', $activeStatuses))
                ->orderBy('room_number')
                ->get(['id', 'room_number', 'floor']),
        ]);
    }
}
