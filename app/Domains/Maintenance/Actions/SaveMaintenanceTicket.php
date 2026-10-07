<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\Maintenance\Enums\MaintenancePriority;
use App\Domains\Maintenance\Enums\MaintenanceStatus;
use App\Domains\Maintenance\Models\MaintenanceTicket;
use App\Domains\Room\Enums\OperationalStatus;
use App\Domains\Room\Models\Room;
use App\Domains\System\Actions\AuditLogger;
use App\Domains\System\Actions\NumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveMaintenanceTicket
{
    public function __construct(
        private readonly NumberGenerator $numberGenerator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, ?MaintenanceTicket $ticket = null): MaintenanceTicket
    {
        return DB::transaction(function () use ($data, $ticket): MaintenanceTicket {
            $ticket = $ticket?->exists
                ? MaintenanceTicket::query()->lockForUpdate()->findOrFail($ticket->id)
                : new MaintenanceTicket;
            if ($ticket->exists && in_array($ticket->status, [MaintenanceStatus::Verified, MaintenanceStatus::Closed], true)) {
                throw ValidationException::withMessages(['maintenance' => 'Ticket yang sudah diverifikasi tidak dapat diubah.']);
            }

            $room = filled($data['room_id'] ?? null)
                ? Room::query()->lockForUpdate()->findOrFail((int) $data['room_id'])
                : null;
            if (! $room && ! filled($data['location'] ?? null)) {
                throw ValidationException::withMessages(['location' => 'Pilih kamar atau isi lokasi masalah.']);
            }

            $priority = MaintenancePriority::from($data['priority']);
            $blocksRoom = $room && ($priority === MaintenancePriority::Critical || (bool) ($data['blocks_room'] ?? false));
            $oldValues = $ticket->exists ? $ticket->only(['room_id', 'category', 'priority', 'description', 'assigned_to', 'status', 'blocks_room']) : [];

            if (! $ticket->exists) {
                $ticket->ticket_number = $this->numberGenerator->generate('MT');
                $ticket->reported_by = auth()->id();
                $ticket->status = filled($data['assigned_to'] ?? null) ? MaintenanceStatus::Assigned : MaintenanceStatus::Open;
            }

            $ticket->fill([
                'room_id' => $room?->id,
                'location' => filled($data['location'] ?? null) ? trim($data['location']) : null,
                'category' => $data['category'],
                'priority' => $priority,
                'description' => trim($data['description']),
                'assigned_to' => filled($data['assigned_to'] ?? null) ? (int) $data['assigned_to'] : null,
                'blocks_room' => $blocksRoom,
                'before_photo' => $data['before_photo'] ?? $ticket->before_photo,
            ]);

            if ($blocksRoom && ! $ticket->room_status_before_block) {
                $ticket->room_status_before_block = $room->operational_status->value;
            }
            $ticket->save();

            if ($blocksRoom && $room) {
                $room->update([
                    'operational_status' => $priority === MaintenancePriority::Critical
                        ? OperationalStatus::OutOfOrder
                        : OperationalStatus::Maintenance,
                ]);
            }

            $this->auditLogger->log($oldValues === [] ? 'created' : 'updated', 'maintenance', $ticket, $oldValues, $ticket->only([
                'ticket_number', 'room_id', 'category', 'priority', 'assigned_to', 'status', 'blocks_room',
            ]));

            return $ticket->refresh()->load(['room', 'assignee', 'reporter']);
        }, 3);
    }
}
