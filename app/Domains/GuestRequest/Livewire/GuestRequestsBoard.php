<?php

namespace App\Domains\GuestRequest\Livewire;

use App\Domains\GuestRequest\Actions\ManageGuestRequest;
use App\Domains\GuestRequest\Actions\SaveGuestRequest;
use App\Domains\GuestRequest\Enums\GuestRequestCategory;
use App\Domains\GuestRequest\Enums\GuestRequestDepartment;
use App\Domains\GuestRequest\Enums\GuestRequestPriority;
use App\Domains\GuestRequest\Enums\GuestRequestStatus;
use App\Domains\GuestRequest\Models\GuestRequest;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GuestRequestsBoard extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $departmentFilter = '';

    public string $reservationId = '';

    public string $category = 'EXTRA_TOWEL';

    public string $priority = 'NORMAL';

    public string $assignedDepartment = 'HOUSEKEEPING';

    public string $description = '';

    public string $notes = '';

    /** @var array<int, string> */
    public array $assignees = [];

    /** @var array<int, string> */
    public array $completionNotes = [];

    /** @var array<int, string> */
    public array $cancellationReasons = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function save(SaveGuestRequest $action): void
    {
        Gate::authorize('guest_request.create');
        $validated = $this->validate([
            'reservationId' => ['required', 'integer', Rule::exists('reservations', 'id')],
            'category' => ['required', Rule::enum(GuestRequestCategory::class)],
            'priority' => ['required', Rule::enum(GuestRequestPriority::class)],
            'assignedDepartment' => ['required', Rule::enum(GuestRequestDepartment::class)],
            'description' => ['required', 'string', 'min:3', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $request = $action->execute([
            'reservation_id' => (int) $validated['reservationId'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'assigned_department' => $validated['assignedDepartment'],
            'description' => $validated['description'],
            'notes' => $validated['notes'],
        ]);
        $this->reset(['reservationId', 'description', 'notes']);
        $this->category = GuestRequestCategory::ExtraTowel->value;
        $this->priority = GuestRequestPriority::Normal->value;
        $this->assignedDepartment = GuestRequestDepartment::Housekeeping->value;
        session()->flash('status', "Request {$request->request_number} berhasil dibuat.");
    }

    public function assign(int $id, ManageGuestRequest $action): void
    {
        Gate::authorize('guest_request.assign');
        $userId = (int) ($this->assignees[$id] ?? 0);
        if (! $userId) {
            $this->addError('guestRequest', 'Pilih petugas terlebih dahulu.');

            return;
        }
        $action->assign(GuestRequest::query()->findOrFail($id), $userId);
        session()->flash('status', 'Guest request berhasil di-assign.');
    }

    public function start(int $id, ManageGuestRequest $action): void
    {
        Gate::authorize('guest_request.update');
        $action->start(GuestRequest::query()->findOrFail($id), (int) auth()->id());
        session()->flash('status', 'Guest request mulai dikerjakan.');
    }

    public function complete(int $id, ManageGuestRequest $action): void
    {
        Gate::authorize('guest_request.update');
        $action->complete(GuestRequest::query()->findOrFail($id), $this->completionNotes[$id] ?? null);
        unset($this->completionNotes[$id]);
        session()->flash('status', 'Guest request diselesaikan.');
    }

    public function cancel(int $id, ManageGuestRequest $action): void
    {
        Gate::authorize('guest_request.update');
        $action->cancel(GuestRequest::query()->findOrFail($id), $this->cancellationReasons[$id] ?? '');
        unset($this->cancellationReasons[$id]);
        session()->flash('status', 'Guest request dibatalkan.');
    }

    public function render(): View
    {
        $requests = GuestRequest::query()
            ->with(['guest:id,full_name,phone', 'room:id,room_number', 'reservation:id,reservation_number', 'assignee:id,name'])
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('request_number', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhereHas('guest', fn ($guestQuery) => $guestQuery->where('full_name', 'like', "%{$this->search}%"))
                    ->orWhereHas('room', fn ($roomQuery) => $roomQuery->where('room_number', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->departmentFilter, fn ($query) => $query->where('assigned_department', $this->departmentFilter))
            ->orderByRaw("CASE priority WHEN 'URGENT' THEN 1 WHEN 'HIGH' THEN 2 WHEN 'NORMAL' THEN 3 ELSE 4 END")
            ->orderByDesc('requested_at')
            ->paginate(10);

        return view('livewire.guest-requests.guest-requests-board', [
            'requests' => $requests,
            'categories' => GuestRequestCategory::cases(),
            'priorities' => GuestRequestPriority::cases(),
            'departments' => GuestRequestDepartment::cases(),
            'statuses' => GuestRequestStatus::cases(),
            'inHouseReservations' => Reservation::query()
                ->with(['guest:id,full_name', 'room:id,room_number'])
                ->where('reservation_status', ReservationStatus::CheckedIn->value)
                ->orderBy('room_id')
                ->get(['id', 'reservation_number', 'guest_id', 'room_id']),
            'staff' => User::query()->permission('guest_request.update')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
