<?php

namespace App\Domains\Reservation\Livewire;

use App\Domains\Reservation\Actions\CheckOutReservation;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class CheckOutBoard extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var array<int, bool> */
    public array $folioReviewed = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function checkOut(int $id, CheckOutReservation $action): void
    {
        Gate::authorize('checkout.execute');
        $this->resetErrorBag(['checkout', 'folioReviewed']);
        $reservation = Reservation::query()->findOrFail($id);
        $action->execute($reservation, (bool) ($this->folioReviewed[$id] ?? false));
        unset($this->folioReviewed[$id]);
        session()->flash('status', "Check-out {$reservation->reservation_number} berhasil. Kamar masuk antrean housekeeping.");
    }

    public function render(): View
    {
        $departures = Reservation::query()
            ->with(['guest:id,guest_code,full_name,phone', 'room:id,room_number', 'stay'])
            ->where('reservation_status', ReservationStatus::CheckedIn->value)
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('reservation_number', 'like', "%{$this->search}%")
                    ->orWhereHas('guest', fn ($guestQuery) => $guestQuery->where('full_name', 'like', "%{$this->search}%"))
                    ->orWhereHas('room', fn ($roomQuery) => $roomQuery->where('room_number', 'like', "%{$this->search}%"));
            }))
            ->orderBy('check_out_date')
            ->paginate(10);

        return view('livewire.reservations.check-out-board', ['departures' => $departures]);
    }
}
