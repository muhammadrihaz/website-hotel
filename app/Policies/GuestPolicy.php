<?php

namespace App\Policies;

use App\Domains\Guest\Models\Guest;
use App\Models\User;

class GuestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('guest.view');
    }

    public function view(User $user, Guest $guest): bool
    {
        return $user->can('guest.view');
    }

    public function create(User $user): bool
    {
        return $user->can('guest.create');
    }

    public function update(User $user, Guest $guest): bool
    {
        return $user->can('guest.update');
    }
}
