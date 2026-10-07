<?php

namespace Tests\Feature\Auth;

use App\Domains\System\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_and_login_is_audited(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@example.test',
            'password' => 'correct-password',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'staff@example.test',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'authentication',
            'action' => 'login',
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => 'correct-password',
            'is_active' => false,
        ]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'inactive@example.test',
            'password' => 'correct-password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_logout_invalidates_session_and_is_audited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
        ]);
    }
}
