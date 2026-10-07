<?php

namespace App\Policies;

use App\Domains\Room\Models\RoomType;
use App\Models\User;

class RoomTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('room_type.view');
    }

    public function view(User $user, RoomType $roomType): bool
    {
        return $user->can('room_type.view');
    }

    public function create(User $user): bool
    {
        return $user->can('room_type.create');
    }

    public function update(User $user, RoomType $roomType): bool
    {
        return $user->can('room_type.update');
    }

    public function delete(User $user, RoomType $roomType): bool
    {
        return $user->can('room_type.delete');
    }
}
