<?php

namespace Tests\Feature\Foundation;

use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Enums\OccupancyStatus;
use App\Domains\Room\Enums\OperationalStatus;
use App\Domains\Room\Livewire\RoomsManager;
use App\Domains\Room\Models\Room;
use App\Domains\Room\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_is_created_with_independent_statuses(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('room.create', 'web');
        $user->givePermissionTo('room.create');
        $type = $this->roomType();

        Livewire::actingAs($user)->test(RoomsManager::class)
            ->set('roomNumber', '205')
            ->set('floor', 2)
            ->set('roomTypeId', (string) $type->id)
            ->set('baseRate', '300000')
            ->set('occupancyStatus', OccupancyStatus::Vacant->value)
            ->set('housekeepingStatus', HousekeepingStatus::Dirty->value)
            ->set('operationalStatus', OperationalStatus::Maintenance->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rooms', [
            'room_number' => '205',
            'occupancy_status' => 'VACANT',
            'housekeeping_status' => 'DIRTY',
            'operational_status' => 'MAINTENANCE',
        ]);
        $this->assertFalse(Room::query()->where('room_number', '205')->firstOrFail()->isReadyForSale());
    }

    public function test_occupied_room_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('room.delete', 'web');
        $user->givePermissionTo('room.delete');
        $room = Room::query()->create([
            'room_number' => '102', 'floor' => 1, 'room_type_id' => $this->roomType()->id,
            'occupancy_status' => OccupancyStatus::Occupied, 'base_rate' => 250000,
            'capacity_adult' => 2, 'capacity_child' => 0,
        ]);

        Livewire::actingAs($user)->test(RoomsManager::class)
            ->call('delete', $room->id)
            ->assertHasErrors('room');

        $this->assertNotSoftDeleted($room);
    }

    private function roomType(): RoomType
    {
        return RoomType::query()->firstOrCreate(
            ['code' => 'STD'],
            ['name' => 'Standard', 'base_rate' => 250000, 'capacity_adult' => 2, 'capacity_child' => 0, 'is_active' => true],
        );
    }
}
