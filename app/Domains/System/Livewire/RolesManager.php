<?php

namespace App\Domains\System\Livewire;

use App\Domains\System\Actions\SaveRole;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesManager extends Component
{
    use AuthorizesRequests;

    private const PROTECTED_ROLES = [
        'Owner', 'Manager', 'Receptionist', 'Housekeeping', 'Maintenance', 'Finance', 'Administrator',
    ];

    public ?int $roleId = null;

    public string $name = '';

    /** @var list<string> */
    public array $selectedPermissions = [];

    public function edit(int $id): void
    {
        Gate::authorize('role.update');
        $role = Role::query()->with('permissions')->findOrFail($id);
        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->all();
        $this->resetValidation();
    }

    public function save(SaveRole $action): void
    {
        $role = $this->roleId ? Role::query()->findOrFail($this->roleId) : null;
        Gate::authorize($role ? 'role.update' : 'role.create');

        $validated = $this->validate([
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('roles', 'name')->ignore($this->roleId)->where('guard_name', 'web'),
            ],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $name = $role && in_array($role->name, self::PROTECTED_ROLES, true)
            ? $role->name
            : trim($validated['name']);

        $action->execute($name, $validated['selectedPermissions'], $role);
        session()->flash('status', $role ? 'Role dan permission berhasil diperbarui.' : 'Role berhasil dibuat.');
        $this->resetForm();
    }

    public function selectGroup(string $prefix): void
    {
        Gate::authorize($this->roleId ? 'role.update' : 'role.create');
        $group = Permission::query()->where('name', 'like', $prefix.'.%')->pluck('name')->all();
        $this->selectedPermissions = array_values(array_unique([...$this->selectedPermissions, ...$group]));
    }

    public function resetForm(): void
    {
        $this->reset(['roleId', 'name', 'selectedPermissions']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('name')->get();

        return view('livewire.system.roles-manager', [
            'roles' => Role::query()->withCount(['permissions', 'users'])->orderBy('name')->get(),
            'permissionGroups' => $permissions->groupBy(fn (Permission $permission) => str($permission->name)->before('.')->toString()),
            'protectedRoles' => self::PROTECTED_ROLES,
        ]);
    }
}
