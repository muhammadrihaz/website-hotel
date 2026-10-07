<?php

namespace Tests\Feature\Foundation;

use App\Domains\System\Livewire\UsersManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_create_user_and_assign_non_owner_role(): void
    {
        $admin = User::factory()->create();
        foreach (['user.create', 'user.assign_role'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $admin->givePermissionTo(['user.create', 'user.assign_role']);
        Role::findOrCreate('Receptionist', 'web');

        Livewire::actingAs($admin)->test(UsersManager::class)
            ->set('name', 'Front Office Agent')
            ->set('email', 'frontoffice@example.test')
            ->set('password', 'secure-password')
            ->set('passwordConfirmation', 'secure-password')
            ->set('roles', ['Receptionist'])
            ->call('save')
            ->assertHasNoErrors();

        $created = User::query()->where('email', 'frontoffice@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('secure-password', $created->password));
        $this->assertTrue($created->hasRole('Receptionist'));
        $this->assertDatabaseHas('audit_logs', ['module' => 'user', 'action' => 'created']);
    }

    public function test_assigning_owner_requires_dedicated_permission(): void
    {
        $admin = User::factory()->create();
        foreach (['user.create', 'user.assign_role'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $admin->givePermissionTo(['user.create', 'user.assign_role']);
        Role::findOrCreate('Owner', 'web');

        Livewire::actingAs($admin)->test(UsersManager::class)
            ->set('name', 'Unauthorized Owner')
            ->set('email', 'owner@example.test')
            ->set('password', 'secure-password')
            ->set('passwordConfirmation', 'secure-password')
            ->set('roles', ['Owner'])
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'owner@example.test']);
    }

    public function test_authorized_admin_can_deactivate_user_and_receives_notification(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create(['is_active' => true]);
        Permission::findOrCreate('user.deactivate', 'web');
        $admin->givePermissionTo('user.deactivate');

        Livewire::actingAs($admin)->test(UsersManager::class)
            ->call('toggleActive', $target->id)
            ->assertHasNoErrors()
            ->assertSee('Pengguna dinonaktifkan.');

        $this->assertFalse($target->fresh()->is_active);
    }
}
