<?php

namespace App\Domains\System\Actions;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SaveRole
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /** @param list<string> $permissions */
    public function execute(string $name, array $permissions, ?Role $role = null): Role
    {
        return DB::transaction(function () use ($name, $permissions, $role): Role {
            $role ??= new Role(['guard_name' => 'web']);
            $oldValues = $role->exists
                ? ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->all()]
                : [];

            if ($role->exists && $role->name === 'Owner') {
                $name = 'Owner';
                $permissions = Permission::query()->where('guard_name', 'web')->pluck('name')->all();
            }

            $role->name = trim($name);
            $role->guard_name = 'web';
            $role->save();
            $role->syncPermissions($permissions);

            $this->auditLogger->log(
                $oldValues === [] ? 'created' : 'updated',
                'role',
                $role,
                $oldValues,
                ['name' => $role->name, 'permissions' => $role->permissions()->pluck('name')->all()],
            );

            return $role->refresh();
        });
    }
}
