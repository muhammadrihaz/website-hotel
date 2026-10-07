<?php

namespace Tests\Feature\Operations;

use App\Domains\ShiftHandover\Actions\ManageShiftHandover;
use App\Domains\ShiftHandover\Enums\HandoverItemPriority;
use App\Domains\ShiftHandover\Enums\HandoverItemStatus;
use App\Domains\ShiftHandover\Enums\ShiftHandoverStatus;
use App\Domains\ShiftHandover\Enums\ShiftType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFrontOfficeData;
use Tests\TestCase;

class ShiftHandoverTest extends TestCase
{
    use CreatesFrontOfficeData, RefreshDatabase;

    public function test_handover_preserves_unfinished_items_for_the_next_shift(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $this->actingAs($sender);
        $room = $this->createRoom();
        $action = app(ManageShiftHandover::class);

        $handover = $action->create([
            'shift_date' => today()->toDateString(),
            'shift_type' => ShiftType::Morning->value,
            'notes' => 'Morning shift notes.',
        ]);
        $item = $action->addItem($handover, [
            'room_id' => $room->id,
            'category' => 'ROOM',
            'description' => 'Follow up late checkout.',
            'priority' => HandoverItemPriority::High->value,
        ]);
        $handover = $action->handover($handover, $recipient->id);
        $handover = $action->acknowledge($handover);

        $this->assertSame(ShiftHandoverStatus::Acknowledged, $handover->status);
        $this->assertDatabaseHas('shift_handover_items', ['id' => $item->id, 'status' => HandoverItemStatus::Open->value]);

        $item = $action->updateItemStatus($item, HandoverItemStatus::Completed, $recipient->id);
        $this->assertSame(HandoverItemStatus::Completed, $item->status);
        $this->assertDatabaseHas('audit_logs', ['module' => 'shift_handover', 'action' => 'item_status_updated']);
    }
}
