<?php

namespace App\Domains\LostFound\Livewire;

use App\Domains\LostFound\Actions\SaveLostFoundItem;
use App\Domains\LostFound\Actions\UpdateLostFoundStatus;
use App\Domains\LostFound\Enums\LostFoundStatus;
use App\Domains\LostFound\Models\LostFoundItem;
use App\Domains\Reservation\Models\Reservation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class LostFoundBoard extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $itemName = '';

    public string $description = '';

    public string $foundLocation = '';

    public string $foundDate = '';

    public string $reservationId = '';

    public string $storageLocation = '';

    public string $notes = '';

    public $photo;

    /** @var array<int, string> */
    public array $statusNotes = [];

    public function mount(): void
    {
        $this->foundDate = today(config('app.timezone'))->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function save(SaveLostFoundItem $action): void
    {
        Gate::authorize('lost_found.create');
        $validated = $this->validate([
            'itemName' => ['required', 'string', 'min:2', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'foundLocation' => ['required', 'string', 'max:160'],
            'foundDate' => ['required', 'date', 'before_or_equal:today'],
            'reservationId' => ['nullable', 'integer', Rule::exists('reservations', 'id')],
            'storageLocation' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photoPath = $this->photo?->store('lost-found', 'local');
        try {
            $item = $action->execute([
                'item_name' => $validated['itemName'],
                'description' => $validated['description'],
                'found_location' => $validated['foundLocation'],
                'found_date' => $validated['foundDate'],
                'reservation_id' => filled($validated['reservationId']) ? (int) $validated['reservationId'] : null,
                'storage_location' => $validated['storageLocation'],
                'notes' => $validated['notes'],
                'photo' => $photoPath,
            ]);
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }
            throw $exception;
        }

        $this->reset(['itemName', 'description', 'foundLocation', 'reservationId', 'storageLocation', 'notes', 'photo']);
        $this->foundDate = today(config('app.timezone'))->toDateString();
        session()->flash('status', "Item {$item->code} berhasil dicatat.");
    }

    public function setStatus(int $id, string $status, UpdateLostFoundStatus $action): void
    {
        Gate::authorize('lost_found.update');
        $target = LostFoundStatus::from($status);
        $action->execute(LostFoundItem::query()->findOrFail($id), $target, $this->statusNotes[$id] ?? null);
        unset($this->statusNotes[$id]);
        session()->flash('status', "Status Lost & Found diubah menjadi {$target->label()}.");
    }

    public function render(): View
    {
        $items = LostFoundItem::query()
            ->with(['guest:id,full_name,phone', 'reservation:id,reservation_number', 'finder:id,name'])
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('code', 'like', "%{$this->search}%")
                    ->orWhere('item_name', 'like', "%{$this->search}%")
                    ->orWhere('found_location', 'like', "%{$this->search}%")
                    ->orWhereHas('guest', fn ($guestQuery) => $guestQuery->where('full_name', 'like', "%{$this->search}%"));
            }))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('found_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.lost-found.lost-found-board', [
            'items' => $items,
            'statuses' => LostFoundStatus::cases(),
            'reservations' => Reservation::query()->with('guest:id,full_name')->latest('id')->limit(100)->get(['id', 'reservation_number', 'guest_id']),
        ]);
    }
}
