<?php

namespace Tests\Feature\Operations;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_routes_enforce_granular_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $routes = [
            'operations.housekeeping.index' => 'housekeeping.view',
            'operations.maintenance.index' => 'maintenance.view',
            'operations.guest-requests.index' => 'guest_request.view',
            'operations.lost-found.index' => 'lost_found.view',
            'staff.shift-handover.index' => 'shift_handover.view',
        ];

        foreach ($routes as $route => $permission) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
            $user->givePermissionTo($permission);
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }
}
