<?php

namespace App\Policies;

use App\Domains\Reservation\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reservation.view');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $user->can('reservation.view');
    }

    public function create(User $user): bool
    {
        return $user->can('reservation.create');
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->can('reservation.update') && $reservation->reservation_status->canBeEdited();
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $user->can('reservation.cancel') && $reservation->reservation_status->canBeEdited();
    }
}
