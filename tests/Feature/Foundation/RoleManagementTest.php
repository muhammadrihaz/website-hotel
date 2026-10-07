<?php

namespace Tests\Feature\Foundation;

use App\Domains\System\Livewire\RolesManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permissions_can_be_managed_and_are_audited(): void
    {
        $user = User::factory()->create();
        foreach (['role.create', 'room.view', 'room.update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo('role.create');

        Livewire::actingAs($user)->test(RolesManager::class)
            ->set('name', 'Room Supervisor')
            ->set('selectedPermissions', ['room.view', 'room.update'])
            ->call('save')
            ->assertHasNoErrors();

        $role = Role::findByName('Room Supervisor');
        $this->assertTrue($role->hasAllPermissions(['room.view', 'room.update']));
        $this->assertDatabaseHas('audit_logs', ['module' => 'role', 'action' => 'created']);
    }
}
