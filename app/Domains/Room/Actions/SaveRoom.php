<?php

namespace App\Domains\Room\Actions;

use App\Domains\Room\Models\Room;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;

class SaveRoom
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, ?Room $room = null): Room
    {
        return DB::transaction(function () use ($data, $room): Room {
            $room ??= new Room;
            $oldValues = $room->exists ? $room->only($this->auditedFields()) : [];
            $room->fill($data)->save();

            $this->auditLogger->log(
                $oldValues === [] ? 'created' : 'updated',
                'room',
                $room,
                $oldValues,
                $room->only($this->auditedFields()),
            );

            return $room->refresh();
        });
    }

    /** @return list<string> */
    private function auditedFields(): array
    {
        return [
            'room_number', 'floor', 'room_type_id', 'occupancy_status',
            'housekeeping_status', 'operational_status', 'base_rate',
            'capacity_adult', 'capacity_child', 'description', 'notes', 'is_active',
        ];
    }
}
