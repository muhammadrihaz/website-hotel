<?php

namespace Tests\Feature\Operations;

use App\Domains\Housekeeping\Actions\CreateHousekeepingTask;
use App\Domains\Housekeeping\Actions\ManageHousekeepingTask;
use App\Domains\Housekeeping\Enums\HousekeepingPriority;
use App\Domains\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domains\Room\Enums\HousekeepingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesFrontOfficeData;
use Tests\TestCase;

class HousekeepingWorkflowTest extends TestCase
{
    use CreatesFrontOfficeData, RefreshDatabase;

    public function test_room_cannot_be_ready_until_mandatory_checklist_is_complete_and_verified(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $room = $this->createRoom(['housekeeping_status' => HousekeepingStatus::Dirty]);
        $task = app(CreateHousekeepingTask::class)->execute($room, null, HousekeepingPriority::High, 'Checkout cleaning');

        $this->assertCount(13, $task->checklistItems);
        $task = app(ManageHousekeepingTask::class)->start($task, $user->id);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'housekeeping_status' => HousekeepingStatus::Cleaning->value]);

        try {
            app(ManageHousekeepingTask::class)->complete($task);
            $this->fail('Incomplete mandatory checklist should reject completion.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('checklist', $exception->errors());
        }

        foreach ($task->checklistItems as $item) {
            $task = app(ManageHousekeepingTask::class)->toggleChecklist($task, $item->id, true, $user->id);
        }

        $task = app(ManageHousekeepingTask::class)->complete($task);
        $this->assertSame(HousekeepingTaskStatus::Completed, $task->status);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'housekeeping_status' => HousekeepingStatus::Clean->value]);

        $task = app(ManageHousekeepingTask::class)->verify($task, $user->id);
        $this->assertSame(HousekeepingTaskStatus::Verified, $task->status);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'housekeeping_status' => HousekeepingStatus::Ready->value]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'housekeeping', 'action' => 'verified']);
    }
}
