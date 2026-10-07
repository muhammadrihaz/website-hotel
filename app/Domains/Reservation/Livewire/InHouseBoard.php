<?php

namespace App\Domains\Reservation\Livewire;

use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class InHouseBoard extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $stays = Reservation::query()
            ->with(['guest:id,guest_code,full_name,phone', 'room:id,room_number,floor', 'roomType:id,name', 'stay'])
            ->where('reservation_status', ReservationStatus::CheckedIn->value)
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('reservation_number', 'like', "%{$this->search}%")
                    ->orWhereHas('guest', fn ($guestQuery) => $guestQuery->where('full_name', 'like', "%{$this->search}%"))
                    ->orWhereHas('room', fn ($roomQuery) => $roomQuery->where('room_number', 'like', "%{$this->search}%"));
            }))
            ->orderBy('check_out_date')
            ->paginate(12);

        return view('livewire.reservations.in-house-board', ['stays' => $stays]);
    }
}
