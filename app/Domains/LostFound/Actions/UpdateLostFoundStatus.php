<?php

namespace App\Domains\LostFound\Actions;

use App\Domains\LostFound\Enums\LostFoundStatus;
use App\Domains\LostFound\Models\LostFoundItem;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateLostFoundStatus
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(LostFoundItem $item, LostFoundStatus $target, ?string $notes = null): LostFoundItem
    {
        return DB::transaction(function () use ($item, $target, $notes): LostFoundItem {
            $locked = LostFoundItem::query()->lockForUpdate()->findOrFail($item->id);
            $allowed = match ($locked->status) {
                LostFoundStatus::Found => [LostFoundStatus::Stored, LostFoundStatus::Claimed, LostFoundStatus::Disposed],
                LostFoundStatus::Stored => [LostFoundStatus::Claimed, LostFoundStatus::Disposed],
                LostFoundStatus::Claimed => [LostFoundStatus::Stored, LostFoundStatus::Returned],
                LostFoundStatus::Returned, LostFoundStatus::Disposed => [],
            };
            if (! in_array($target, $allowed, true)) {
                throw ValidationException::withMessages(['lostFound' => 'Perubahan status Lost & Found tidak valid.']);
            }

            $oldStatus = $locked->status;
            $locked->update([
                'status' => $target,
                'notes' => filled($notes) ? trim($notes) : $locked->notes,
                'resolved_at' => in_array($target, [LostFoundStatus::Returned, LostFoundStatus::Disposed], true) ? now() : null,
            ]);
            $this->auditLogger->log('status_updated', 'lost_found', $locked, ['status' => $oldStatus->value], [
                'status' => $target->value,
                'notes' => $locked->notes,
            ]);

            return $locked->refresh()->load(['guest', 'reservation', 'finder']);
        }, 3);
    }
}
