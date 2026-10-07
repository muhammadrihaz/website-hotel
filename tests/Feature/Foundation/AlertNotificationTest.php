<?php

namespace Tests\Feature\Foundation;

use App\Domains\Room\Livewire\RoomTypesManager;
use App\Domains\Room\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AlertNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_update_and_delete_actions_render_success_notifications(): void
    {
        $user = User::factory()->create();
        foreach (['room_type.create', 'room_type.update', 'room_type.delete'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo(['room_type.create', 'room_type.update', 'room_type.delete']);

        $component = Livewire::actingAs($user)->test(RoomTypesManager::class)
            ->set('name', 'Notification Test')
            ->set('code', 'NTF')
            ->set('baseRate', '350000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSeeHtml('data-alert-notification')
            ->assertSeeHtml('data-alert-type="success"')
            ->assertSee('Tipe kamar berhasil dibuat.');

        $roomType = RoomType::query()->where('code', 'NTF')->firstOrFail();

        $component
            ->call('edit', $roomType->id)
            ->set('name', 'Notification Updated')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Tipe kamar berhasil diperbarui.');

        $component
            ->call('delete', $roomType->id)
            ->assertHasNoErrors()
            ->assertSee('Tipe kamar berhasil dihapus.');

        $this->assertSoftDeleted($roomType);
    }

    public function test_validation_failure_renders_error_notification(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('room_type.create', 'web');
        $user->givePermissionTo('room_type.create');

        Livewire::actingAs($user)->test(RoomTypesManager::class)
            ->call('save')
            ->assertHasErrors(['name', 'code'])
            ->assertSeeHtml('data-alert-notification')
            ->assertSeeHtml('data-alert-type="error"')
            ->assertSee('Tindakan belum berhasil');
    }
}
