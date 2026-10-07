<?php

namespace App\Domains\Room\Actions;

use App\Domains\Room\Models\RoomType;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;

class SaveRoomType
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, ?RoomType $roomType = null): RoomType
    {
        return DB::transaction(function () use ($data, $roomType): RoomType {
            $roomType ??= new RoomType;
            $oldValues = $roomType->exists ? $roomType->only($this->auditedFields()) : [];

            $data['code'] = mb_strtoupper(trim((string) $data['code']));
            $roomType->fill($data)->save();

            $this->auditLogger->log(
                $oldValues === [] ? 'created' : 'updated',
                'room_type',
                $roomType,
                $oldValues,
                $roomType->only($this->auditedFields()),
            );

            return $roomType->refresh();
        });
    }

    /** @return list<string> */
    private function auditedFields(): array
    {
        return ['name', 'code', 'description', 'base_rate', 'capacity_adult', 'capacity_child', 'is_active'];
    }
}
