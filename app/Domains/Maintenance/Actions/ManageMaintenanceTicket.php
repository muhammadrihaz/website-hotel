<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\Maintenance\Enums\MaintenanceStatus;
use App\Domains\Maintenance\Models\MaintenanceTicket;
use App\Domains\Room\Enums\OperationalStatus;
use App\Domains\Room\Models\Room;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageMaintenanceTicket
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function assign(MaintenanceTicket $ticket, int $userId): MaintenanceTicket
    {
        return $this->transition($ticket, [MaintenanceStatus::Open, MaintenanceStatus::Assigned], MaintenanceStatus::Assigned, [
            'assigned_to' => $userId,
        ], 'assigned');
    }

    public function start(MaintenanceTicket $ticket, int $userId): MaintenanceTicket
    {
        return $this->transition($ticket, [MaintenanceStatus::Open, MaintenanceStatus::Assigned, MaintenanceStatus::Waiting], MaintenanceStatus::InProgress, [
            'assigned_to' => $ticket->assigned_to ?: $userId,
            'started_at' => $ticket->started_at ?: now(),
        ], 'started');
    }

    public function wait(MaintenanceTicket $ticket): MaintenanceTicket
    {
        return $this->transition($ticket, [MaintenanceStatus::InProgress], MaintenanceStatus::Waiting, [], 'waiting');
    }

    public function resolve(MaintenanceTicket $ticket, string $resolution, ?string $afterPhoto = null): MaintenanceTicket
    {
        if (mb_strlen(trim($resolution)) < 3) {
            throw ValidationException::withMessages(['resolution' => 'Resolusi minimal 3 karakter.']);
        }

        return $this->transition($ticket, [MaintenanceStatus::Assigned, MaintenanceStatus::InProgress, MaintenanceStatus::Waiting], MaintenanceStatus::Resolved, [
            'resolution' => trim($resolution),
            'resolved_at' => now(),
            'after_photo' => $afterPhoto ?: $ticket->after_photo,
        ], 'resolved');
    }

    public function verify(MaintenanceTicket $ticket, int $userId): MaintenanceTicket
    {
        return DB::transaction(function () use ($ticket, $userId): MaintenanceTicket {
            $locked = MaintenanceTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            if ($locked->status !== MaintenanceStatus::Resolved) {
                $this->invalid('Hanya ticket Resolved yang dapat diverifikasi.');
            }

            $oldStatus = $locked->status;
            $locked->update([
                'status' => MaintenanceStatus::Verified,
                'verified_at' => now(),
                'verified_by' => $userId,
            ]);
            $this->releaseRoomIfSafe($locked);
            $this->audit($locked, 'verified', $oldStatus);

            return $locked->refresh()->load(['room', 'assignee', 'reporter', 'verifier']);
        }, 3);
    }

    public function close(MaintenanceTicket $ticket): MaintenanceTicket
    {
        return $this->transition($ticket, [MaintenanceStatus::Verified], MaintenanceStatus::Closed, [], 'closed');
    }

    /**
     * @param  list<MaintenanceStatus>  $allowed
     * @param  array<string, mixed>  $values
     */
    private function transition(MaintenanceTicket $ticket, array $allowed, MaintenanceStatus $target, array $values, string $action): MaintenanceTicket
    {
        return DB::transaction(function () use ($ticket, $allowed, $target, $values, $action): MaintenanceTicket {
            $locked = MaintenanceTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            if (! in_array($locked->status, $allowed, true)) {
                $this->invalid("Ticket tidak dapat diubah menjadi {$target->label()} dari status saat ini.");
            }

            $oldStatus = $locked->status;
            $locked->update([...$values, 'status' => $target]);
            $this->audit($locked, $action, $oldStatus);

            return $locked->refresh()->load(['room', 'assignee', 'reporter', 'verifier']);
        }, 3);
    }

    private function releaseRoomIfSafe(MaintenanceTicket $ticket): void
    {
        if (! $ticket->blocks_room || ! $ticket->room_id) {
            return;
        }

        $room = Room::query()->lockForUpdate()->findOrFail($ticket->room_id);
        $hasOtherBlocker = MaintenanceTicket::query()
            ->where('room_id', $ticket->room_id)
            ->where('blocks_room', true)
            ->whereKeyNot($ticket->id)
            ->whereIn('status', array_map(fn (MaintenanceStatus $status) => $status->value, MaintenanceStatus::blocking()))
            ->exists();

        if (! $hasOtherBlocker) {
            $room->update(['operational_status' => OperationalStatus::Available]);
        }
    }

    private function audit(MaintenanceTicket $ticket, string $action, MaintenanceStatus $oldStatus): void
    {
        $this->auditLogger->log($action, 'maintenance', $ticket, ['status' => $oldStatus->value], [
            'status' => $ticket->status->value,
            'assigned_to' => $ticket->assigned_to,
            'resolution' => $ticket->resolution,
        ]);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['maintenance' => $message]);
    }
}
