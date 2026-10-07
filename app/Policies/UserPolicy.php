<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    public function create(User $user): bool
    {
        return $user->can('user.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('user.update');
    }

    public function deactivate(User $user, User $target): bool
    {
        return $user->can('user.deactivate') && $user->isNot($target);
    }
}
