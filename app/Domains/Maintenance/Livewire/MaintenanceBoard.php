<?php

namespace App\Domains\Maintenance\Livewire;

use App\Domains\Maintenance\Actions\ManageMaintenanceTicket;
use App\Domains\Maintenance\Actions\SaveMaintenanceTicket;
use App\Domains\Maintenance\Enums\MaintenanceCategory;
use App\Domains\Maintenance\Enums\MaintenancePriority;
use App\Domains\Maintenance\Enums\MaintenanceStatus;
use App\Domains\Maintenance\Models\MaintenanceTicket;
use App\Domains\Room\Models\Room;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class MaintenanceBoard extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $priorityFilter = '';

    public string $roomId = '';

    public string $location = '';

    public string $category = 'AIR_CONDITIONER';

    public string $priority = 'NORMAL';

    public string $description = '';

    public bool $blocksRoom = false;

    public $beforePhoto;

    /** @var array<int, string> */
    public array $assignees = [];

    /** @var array<int, string> */
    public array $resolutions = [];

    /** @var array<int, mixed> */
    public array $afterPhotos = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function save(SaveMaintenanceTicket $action): void
    {
        Gate::authorize('maintenance.create');
        $validated = $this->validate([
            'roomId' => ['nullable', 'integer', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
            'location' => ['nullable', 'string', 'max:160'],
            'category' => ['required', Rule::enum(MaintenanceCategory::class)],
            'priority' => ['required', Rule::enum(MaintenancePriority::class)],
            'description' => ['required', 'string', 'min:5', 'max:3000'],
            'blocksRoom' => ['boolean'],
            'beforePhoto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photoPath = $this->beforePhoto?->store('maintenance/before', 'local');
        try {
            $ticket = $action->execute([
                'room_id' => filled($validated['roomId']) ? (int) $validated['roomId'] : null,
                'location' => $validated['location'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'description' => $validated['description'],
                'blocks_room' => $validated['blocksRoom'],
                'before_photo' => $photoPath,
            ]);
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }
            throw $exception;
        }

        $this->reset(['roomId', 'location', 'description', 'blocksRoom', 'beforePhoto']);
        $this->category = MaintenanceCategory::AirConditioner->value;
        $this->priority = MaintenancePriority::Normal->value;
        session()->flash('status', "Ticket {$ticket->ticket_number} berhasil dibuat.");
    }

    public function assign(int $id, ManageMaintenanceTicket $action): void
    {
        Gate::authorize('maintenance.assign');
        $userId = (int) ($this->assignees[$id] ?? 0);
        if (! $userId) {
            $this->addError('maintenance', 'Pilih petugas maintenance.');

            return;
        }
        $action->assign(MaintenanceTicket::query()->findOrFail($id), $userId);
        session()->flash('status', 'Petugas maintenance berhasil ditetapkan.');
    }

    public function start(int $id, ManageMaintenanceTicket $action): void
    {
        Gate::authorize('maintenance.update');
        $action->start(MaintenanceTicket::query()->findOrFail($id), (int) auth()->id());
        session()->flash('status', 'Pengerjaan maintenance dimulai.');
    }

    public function waitForPart(int $id, ManageMaintenanceTicket $action): void
    {
        Gate::authorize('maintenance.update');
        $action->wait(MaintenanceTicket::query()->findOrFail($id));
        session()->flash('status', 'Ticket dipindahkan ke status Waiting.');
    }

    public function resolve(int $id, ManageMaintenanceTicket $action): void
    {
        Gate::authorize('maintenance.update');
        $this->validate([
            "resolutions.{$id}" => ['required', 'string', 'min:3', 'max:3000'],
            "afterPhotos.{$id}" => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $photoPath = isset($this->afterPhotos[$id]) ? $this->afterPhotos[$id]->store('maintenance/after', 'local') : null;
        try {
            $action->resolve(MaintenanceTicket::query()->findOrFail($id), $this->resolutions[$id], $photoPath);
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }
            throw $exception;
        }
        unset($this->resolutions[$id], $this->afterPhotos[$id]);
        session()->flash('status', 'Ticket diselesaikan dan menunggu verifikasi.');
    }

    public function verify(int $id, ManageMaintenanceTicket $action): void
    {
        Gate::authorize('maintenance.verify');
        $action->verify(MaintenanceTicket::query()->findOrFail($id), (int) auth()->id());
        session()->flash('status', 'Perbaikan diverifikasi; blokir kamar dilepas bila aman.');
    }

    public function close(int $id, ManageMaintenanceTicket $action): void
    {
        Gate::authorize('maintenance.verify');
        $action->close(MaintenanceTicket::query()->findOrFail($id));
        session()->flash('status', 'Ticket maintenance ditutup.');
    }

    public function render(): View
    {
        $tickets = MaintenanceTicket::query()
            ->with(['room:id,room_number,floor', 'assignee:id,name', 'reporter:id,name', 'verifier:id,name'])
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('ticket_number', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhereHas('room', fn ($roomQuery) => $roomQuery->where('room_number', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->priorityFilter, fn ($query) => $query->where('priority', $this->priorityFilter))
            ->orderByRaw("CASE priority WHEN 'CRITICAL' THEN 1 WHEN 'HIGH' THEN 2 WHEN 'NORMAL' THEN 3 ELSE 4 END")
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.maintenance.maintenance-board', [
            'tickets' => $tickets,
            'categories' => MaintenanceCategory::cases(),
            'priorities' => MaintenancePriority::cases(),
            'statuses' => MaintenanceStatus::cases(),
            'rooms' => Room::query()->where('is_active', true)->orderBy('room_number')->get(['id', 'room_number', 'floor']),
            'staff' => User::query()->permission('maintenance.update')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
