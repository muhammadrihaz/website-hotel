<?php

namespace App\Domains\Room\Actions;

use App\Domains\Room\Models\RoomType;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteRoomType
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(RoomType $roomType): void
    {
        DB::transaction(function () use ($roomType): void {
            if ($roomType->rooms()->exists()) {
                throw ValidationException::withMessages([
                    'roomType' => 'Tipe kamar masih digunakan dan tidak dapat dihapus.',
                ]);
            }

            $oldValues = $roomType->only(['name', 'code', 'is_active']);
            $roomType->delete();
            $this->auditLogger->log('deleted', 'room_type', $roomType, $oldValues);
        });
    }
}
