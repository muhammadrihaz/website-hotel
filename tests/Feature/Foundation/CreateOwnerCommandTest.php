<?php

namespace Tests\Feature\Foundation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateOwnerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_active_owner_with_a_hidden_password_prompt(): void
    {
        Role::findOrCreate('Owner', 'web');

        $this->artisan('app:create-owner', [
            '--name' => 'Hotel Owner',
            '--email' => 'owner@example.test',
        ])
            ->expectsQuestion('Password owner (minimal 12 karakter)', 'secure-owner-password')
            ->expectsQuestion('Ulangi password owner', 'secure-owner-password')
            ->expectsOutput('Akun Owner owner@example.test siap digunakan.')
            ->assertSuccessful();

        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();

        $this->assertSame('Hotel Owner', $owner->name);
        $this->assertTrue($owner->is_active);
        $this->assertTrue(Hash::check('secure-owner-password', $owner->password));
        $this->assertTrue($owner->hasRole('Owner'));
    }

    public function test_it_rejects_an_unconfirmed_password(): void
    {
        Role::findOrCreate('Owner', 'web');

        $this->artisan('app:create-owner', [
            '--name' => 'Hotel Owner',
            '--email' => 'owner@example.test',
        ])
            ->expectsQuestion('Password owner (minimal 12 karakter)', 'secure-owner-password')
            ->expectsQuestion('Ulangi password owner', 'different-password')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'owner@example.test']);
    }
}
