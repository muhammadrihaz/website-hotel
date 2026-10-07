<?php

namespace Tests\Feature\FrontOffice;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FrontOfficeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_office_routes_enforce_granular_permissions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('front-office.reservations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('front-office.guests.index'))->assertForbidden();
        $this->actingAs($user)->get(route('front-office.check-in.index'))->assertForbidden();

        foreach (['reservation.view', 'guest.view', 'checkin.execute'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }

        $this->actingAs($user)->get(route('front-office.reservations.index'))->assertOk();
        $this->actingAs($user)->get(route('front-office.guests.index'))->assertOk();
        $this->actingAs($user)->get(route('front-office.check-in.index'))->assertOk();
    }
}
