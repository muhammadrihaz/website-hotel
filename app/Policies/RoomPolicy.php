<?php

namespace App\Policies;

use App\Domains\Room\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('room.view');
    }

    public function view(User $user, Room $room): bool
    {
        return $user->can('room.view');
    }

    public function create(User $user): bool
    {
        return $user->can('room.create');
    }

    public function update(User $user, Room $room): bool
    {
        return $user->can('room.update');
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->can('room.delete');
    }
}
