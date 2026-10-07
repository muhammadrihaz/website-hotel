<?php

namespace App\Domains\Housekeeping\Actions;

use App\Domains\Housekeeping\Enums\HousekeepingPriority;
use App\Domains\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domains\Housekeeping\Models\HousekeepingTask;
use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Models\Room;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;

class CreateHousekeepingTask
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(
        Room $room,
        ?int $reservationId = null,
        HousekeepingPriority $priority = HousekeepingPriority::Normal,
        ?string $notes = null,
    ): HousekeepingTask {
        return DB::transaction(function () use ($room, $reservationId, $priority, $notes): HousekeepingTask {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);

            $task = HousekeepingTask::query()
                ->when(
                    $reservationId,
                    fn ($query) => $query->where('reservation_id', $reservationId),
                    fn ($query) => $query->where('room_id', $room->id)->whereIn('status', [
                        HousekeepingTaskStatus::Pending->value,
                        HousekeepingTaskStatus::Assigned->value,
                        HousekeepingTaskStatus::Cleaning->value,
                        HousekeepingTaskStatus::Completed->value,
                    ]),
                )
                ->lockForUpdate()
                ->first();

            if (! $task) {
                $task = HousekeepingTask::query()->create([
                    'room_id' => $room->id,
                    'reservation_id' => $reservationId,
                    'priority' => $priority,
                    'status' => HousekeepingTaskStatus::Pending,
                    'notes' => $notes,
                ]);

                $this->auditLogger->log('created', 'housekeeping', $task, [], [
                    'room_id' => $room->id,
                    'reservation_id' => $reservationId,
                    'status' => HousekeepingTaskStatus::Pending->value,
                ]);
            }

            $this->ensureChecklist($task);

            if ($room->housekeeping_status !== HousekeepingStatus::Dirty) {
                $room->update(['housekeeping_status' => HousekeepingStatus::Dirty]);
            }

            return $task->refresh()->load('checklistItems');
        }, 3);
    }

    public function ensureChecklist(HousekeepingTask $task): void
    {
        foreach (HousekeepingChecklist::ITEMS as $key => $label) {
            $task->checklistItems()->firstOrCreate(
                ['item_key' => $key],
                ['label' => $label, 'is_mandatory' => true, 'is_completed' => false],
            );
        }
    }
}
