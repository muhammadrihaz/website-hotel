<?php

namespace Tests\Feature\Operations;

use App\Domains\Maintenance\Actions\ManageMaintenanceTicket;
use App\Domains\Maintenance\Actions\SaveMaintenanceTicket;
use App\Domains\Maintenance\Enums\MaintenanceCategory;
use App\Domains\Maintenance\Enums\MaintenancePriority;
use App\Domains\Maintenance\Enums\MaintenanceStatus;
use App\Domains\Room\Enums\OperationalStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFrontOfficeData;
use Tests\TestCase;

class MaintenanceWorkflowTest extends TestCase
{
    use CreatesFrontOfficeData, RefreshDatabase;

    public function test_critical_ticket_blocks_room_until_resolution_is_verified(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $room = $this->createRoom();

        $ticket = app(SaveMaintenanceTicket::class)->execute([
            'room_id' => $room->id,
            'category' => MaintenanceCategory::Electrical->value,
            'priority' => MaintenancePriority::Critical->value,
            'description' => 'Electrical short circuit near the bed.',
            'blocks_room' => false,
        ]);

        $this->assertTrue($ticket->blocks_room);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'operational_status' => OperationalStatus::OutOfOrder->value]);

        $ticket = app(ManageMaintenanceTicket::class)->start($ticket, $user->id);
        $ticket = app(ManageMaintenanceTicket::class)->resolve($ticket, 'Replaced damaged socket and tested load.');
        $this->assertSame(MaintenanceStatus::Resolved, $ticket->status);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'operational_status' => OperationalStatus::OutOfOrder->value]);

        $ticket = app(ManageMaintenanceTicket::class)->verify($ticket, $user->id);
        $this->assertSame(MaintenanceStatus::Verified, $ticket->status);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'operational_status' => OperationalStatus::Available->value]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'maintenance', 'action' => 'verified']);
    }
}
