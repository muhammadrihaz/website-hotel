<?php

namespace Tests\Feature\Foundation;

use App\Domains\Room\Livewire\RoomTypesManager;
use App\Domains\Room\Models\Room;
use App\Domains\Room\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RoomTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_room_type_with_audit_log(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('room_type.create', 'web');
        $user->givePermissionTo('room_type.create');

        Livewire::actingAs($user)->test(RoomTypesManager::class)
            ->set('name', 'Deluxe')
            ->set('code', 'dlx')
            ->set('baseRate', '425000')
            ->set('capacityAdult', 2)
            ->set('capacityChild', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('room_types', ['name' => 'Deluxe', 'code' => 'DLX']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'room_type', 'action' => 'created']);
    }

    public function test_room_type_in_use_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        foreach (['room_type.view', 'room_type.delete'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo(['room_type.view', 'room_type.delete']);
        $roomType = RoomType::query()->create([
            'name' => 'Standard', 'code' => 'STD', 'base_rate' => 200000,
            'capacity_adult' => 2, 'capacity_child' => 0, 'is_active' => true,
        ]);
        Room::query()->create([
            'room_number' => '101', 'floor' => 1, 'room_type_id' => $roomType->id,
            'base_rate' => 200000, 'capacity_adult' => 2, 'capacity_child' => 0,
        ]);

        Livewire::actingAs($user)->test(RoomTypesManager::class)
            ->call('delete', $roomType->id)
            ->assertHasErrors('roomType');

        $this->assertNotSoftDeleted($roomType);
    }
}
