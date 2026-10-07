<?php

namespace App\Domains\Room\Actions;

use App\Domains\Room\Enums\OccupancyStatus;
use App\Domains\Room\Models\Room;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteRoom
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(Room $room): void
    {
        DB::transaction(function () use ($room): void {
            if ($room->occupancy_status !== OccupancyStatus::Vacant) {
                throw ValidationException::withMessages([
                    'room' => 'Kamar occupied/reserved tidak dapat dihapus.',
                ]);
            }

            $oldValues = $room->only(['room_number', 'room_type_id', 'is_active']);
            $room->delete();
            $this->auditLogger->log('deleted', 'room', $room, $oldValues);
        });
    }
}
