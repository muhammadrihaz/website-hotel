<?php

namespace Tests\Feature\Foundation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_open_room_master(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('system.rooms.index'))->assertForbidden();
    }

    public function test_user_with_permission_can_open_room_master(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('room.view', 'web');
        $user->givePermissionTo('room.view');

        $this->actingAs($user)->get(route('system.rooms.index'))->assertOk();
    }

    public function test_inactive_authenticated_user_is_logged_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        Permission::findOrCreate('dashboard.view', 'web');
        $user->givePermissionTo('dashboard.view');

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
