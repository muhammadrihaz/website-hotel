<?php

namespace App\Domains\Housekeeping\Actions;

use App\Domains\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domains\Housekeeping\Models\HousekeepingChecklistItem;
use App\Domains\Housekeeping\Models\HousekeepingTask;
use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Models\Room;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageHousekeepingTask
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function assign(HousekeepingTask $task, int $userId): HousekeepingTask
    {
        return $this->transition($task, function (HousekeepingTask $locked) use ($userId): void {
            if (! in_array($locked->status, [HousekeepingTaskStatus::Pending, HousekeepingTaskStatus::Assigned], true)) {
                $this->invalid('Task hanya dapat di-assign saat Pending atau Assigned.');
            }

            $locked->update([
                'assigned_to' => $userId,
                'status' => HousekeepingTaskStatus::Assigned,
            ]);
        }, 'assigned');
    }

    public function start(HousekeepingTask $task, int $userId): HousekeepingTask
    {
        return DB::transaction(function () use ($task, $userId): HousekeepingTask {
            $locked = HousekeepingTask::query()->lockForUpdate()->findOrFail($task->id);
            if (! in_array($locked->status, [HousekeepingTaskStatus::Pending, HousekeepingTaskStatus::Assigned], true)) {
                $this->invalid('Task tidak dapat mulai dari status saat ini.');
            }

            $room = Room::query()->lockForUpdate()->findOrFail($locked->room_id);
            $oldStatus = $locked->status;
            $locked->update([
                'assigned_to' => $locked->assigned_to ?: $userId,
                'status' => HousekeepingTaskStatus::Cleaning,
                'started_at' => $locked->started_at ?: now(),
            ]);
            $room->update(['housekeeping_status' => HousekeepingStatus::Cleaning]);
            $this->audit($locked, 'started', $oldStatus);

            return $locked->refresh()->load(['room', 'checklistItems', 'assignee']);
        }, 3);
    }

    public function toggleChecklist(HousekeepingTask $task, int $itemId, bool $completed, int $userId): HousekeepingTask
    {
        return DB::transaction(function () use ($task, $itemId, $completed, $userId): HousekeepingTask {
            $locked = HousekeepingTask::query()->lockForUpdate()->findOrFail($task->id);
            if ($locked->status !== HousekeepingTaskStatus::Cleaning) {
                $this->invalid('Checklist hanya dapat diubah saat task sedang Cleaning.');
            }

            $item = HousekeepingChecklistItem::query()
                ->where('housekeeping_task_id', $locked->id)
                ->lockForUpdate()
                ->findOrFail($itemId);
            $item->update([
                'is_completed' => $completed,
                'completed_by' => $completed ? $userId : null,
                'completed_at' => $completed ? now() : null,
            ]);

            return $locked->refresh()->load(['room', 'checklistItems', 'assignee']);
        }, 3);
    }

    public function complete(HousekeepingTask $task): HousekeepingTask
    {
        return DB::transaction(function () use ($task): HousekeepingTask {
            $locked = HousekeepingTask::query()->lockForUpdate()->findOrFail($task->id);
            if ($locked->status !== HousekeepingTaskStatus::Cleaning) {
                $this->invalid('Hanya task Cleaning yang dapat diselesaikan.');
            }

            $hasIncompleteMandatory = $locked->checklistItems()
                ->where('is_mandatory', true)
                ->where('is_completed', false)
                ->exists();
            if ($hasIncompleteMandatory) {
                throw ValidationException::withMessages(['checklist' => 'Semua checklist wajib harus selesai sebelum cleaning ditutup.']);
            }

            $room = Room::query()->lockForUpdate()->findOrFail($locked->room_id);
            $locked->update([
                'status' => HousekeepingTaskStatus::Completed,
                'completed_at' => now(),
            ]);
            $room->update(['housekeeping_status' => HousekeepingStatus::Clean]);
            $this->audit($locked, 'completed', HousekeepingTaskStatus::Cleaning);

            return $locked->refresh()->load(['room', 'checklistItems', 'assignee']);
        }, 3);
    }

    public function verify(HousekeepingTask $task, int $userId): HousekeepingTask
    {
        return DB::transaction(function () use ($task, $userId): HousekeepingTask {
            $locked = HousekeepingTask::query()->lockForUpdate()->findOrFail($task->id);
            if ($locked->status !== HousekeepingTaskStatus::Completed) {
                $this->invalid('Hanya task Completed yang dapat diverifikasi.');
            }

            $room = Room::query()->lockForUpdate()->findOrFail($locked->room_id);
            $locked->update([
                'status' => HousekeepingTaskStatus::Verified,
                'verified_at' => now(),
                'verified_by' => $userId,
            ]);
            $room->update(['housekeeping_status' => HousekeepingStatus::Ready]);
            $this->audit($locked, 'verified', HousekeepingTaskStatus::Completed);

            return $locked->refresh()->load(['room', 'checklistItems', 'assignee', 'verifier']);
        }, 3);
    }

    /** @param callable(HousekeepingTask): void $callback */
    private function transition(HousekeepingTask $task, callable $callback, string $action): HousekeepingTask
    {
        return DB::transaction(function () use ($task, $callback, $action): HousekeepingTask {
            $locked = HousekeepingTask::query()->lockForUpdate()->findOrFail($task->id);
            $oldStatus = $locked->status;
            $callback($locked);
            $this->audit($locked, $action, $oldStatus);

            return $locked->refresh()->load(['room', 'checklistItems', 'assignee']);
        }, 3);
    }

    private function audit(HousekeepingTask $task, string $action, HousekeepingTaskStatus $oldStatus): void
    {
        $this->auditLogger->log($action, 'housekeeping', $task, [
            'status' => $oldStatus->value,
        ], [
            'status' => $task->status->value,
            'assigned_to' => $task->assigned_to,
            'room_id' => $task->room_id,
        ]);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['housekeeping' => $message]);
    }
}
