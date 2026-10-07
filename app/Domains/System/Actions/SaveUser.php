<?php

namespace App\Domains\System\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveUser
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $roles
     */
    public function execute(array $data, array $roles, ?User $user = null): User
    {
        return DB::transaction(function () use ($data, $roles, $user): User {
            $user ??= new User;
            $oldValues = $user->exists
                ? [...$user->only(['name', 'email', 'is_active']), 'roles' => $user->getRoleNames()->all()]
                : [];

            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }

            $user->fill($data)->save();
            $user->syncRoles($roles);

            $newValues = [
                ...$user->only(['name', 'email', 'is_active']),
                'roles' => $user->getRoleNames()->all(),
            ];

            $this->auditLogger->log(
                $oldValues === [] ? 'created' : 'updated',
                'user',
                $user,
                $oldValues,
                $newValues,
            );

            return $user->refresh();
        });
    }
}
