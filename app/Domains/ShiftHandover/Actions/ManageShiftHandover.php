<?php

namespace App\Domains\ShiftHandover\Actions;

use App\Domains\ShiftHandover\Enums\HandoverItemStatus;
use App\Domains\ShiftHandover\Enums\ShiftHandoverStatus;
use App\Domains\ShiftHandover\Models\ShiftHandover;
use App\Domains\ShiftHandover\Models\ShiftHandoverItem;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageShiftHandover
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /** @param array<string, mixed> $data */
    public function create(array $data): ShiftHandover
    {
        return DB::transaction(function () use ($data): ShiftHandover {
            $handover = ShiftHandover::query()->create([
                'shift_date' => $data['shift_date'],
                'shift_type' => $data['shift_type'],
                'created_by' => auth()->id(),
                'handover_to' => filled($data['handover_to'] ?? null) ? (int) $data['handover_to'] : null,
                'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
                'status' => ShiftHandoverStatus::Draft,
            ]);
            $this->auditLogger->log('created', 'shift_handover', $handover, [], [
                'shift_date' => $handover->shift_date->toDateString(),
                'shift_type' => $handover->shift_type->value,
                'handover_to' => $handover->handover_to,
            ]);

            return $handover->refresh()->load(['creator', 'recipient', 'items']);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function addItem(ShiftHandover $handover, array $data): ShiftHandoverItem
    {
        return DB::transaction(function () use ($handover, $data): ShiftHandoverItem {
            $locked = ShiftHandover::query()->lockForUpdate()->findOrFail($handover->id);
            if ($locked->status !== ShiftHandoverStatus::Draft) {
                throw ValidationException::withMessages(['handover' => 'Item hanya dapat ditambahkan pada handover Draft.']);
            }

            $item = $locked->items()->create([
                'room_id' => filled($data['room_id'] ?? null) ? (int) $data['room_id'] : null,
                'reservation_id' => filled($data['reservation_id'] ?? null) ? (int) $data['reservation_id'] : null,
                'category' => $data['category'],
                'description' => trim($data['description']),
                'priority' => $data['priority'],
                'status' => HandoverItemStatus::Open,
            ]);
            $this->auditLogger->log('item_added', 'shift_handover', $locked, [], [
                'item_id' => $item->id,
                'category' => $item->category,
                'priority' => $item->priority->value,
            ]);

            return $item->refresh()->load(['room', 'reservation']);
        }, 3);
    }

    public function updateItemStatus(ShiftHandoverItem $item, HandoverItemStatus $target, int $userId): ShiftHandoverItem
    {
        return DB::transaction(function () use ($item, $target, $userId): ShiftHandoverItem {
            $locked = ShiftHandoverItem::query()->lockForUpdate()->findOrFail($item->id);
            if ($locked->status === HandoverItemStatus::Completed && $target !== HandoverItemStatus::Open) {
                throw ValidationException::withMessages(['handover' => 'Item yang selesai hanya dapat dibuka kembali.']);
            }
            $oldStatus = $locked->status;
            $locked->update([
                'status' => $target,
                'completed_by' => $target === HandoverItemStatus::Completed ? $userId : null,
                'completed_at' => $target === HandoverItemStatus::Completed ? now() : null,
            ]);
            $this->auditLogger->log('item_status_updated', 'shift_handover', $locked->shift_handover_id, ['status' => $oldStatus->value], [
                'item_id' => $locked->id,
                'status' => $target->value,
            ]);

            return $locked->refresh()->load(['room', 'reservation']);
        }, 3);
    }

    public function handover(ShiftHandover $handover, int $recipientId): ShiftHandover
    {
        return DB::transaction(function () use ($handover, $recipientId): ShiftHandover {
            $locked = ShiftHandover::query()->lockForUpdate()->findOrFail($handover->id);
            if ($locked->status !== ShiftHandoverStatus::Draft || ! $locked->items()->exists()) {
                throw ValidationException::withMessages(['handover' => 'Handover harus berstatus Draft dan memiliki minimal satu item.']);
            }
            $locked->update([
                'handover_to' => $recipientId,
                'status' => ShiftHandoverStatus::HandedOver,
                'handed_over_at' => now(),
            ]);
            $this->auditLogger->log('handed_over', 'shift_handover', $locked, ['status' => ShiftHandoverStatus::Draft->value], [
                'status' => ShiftHandoverStatus::HandedOver->value,
                'handover_to' => $recipientId,
            ]);

            return $locked->refresh()->load(['creator', 'recipient', 'items']);
        }, 3);
    }

    public function acknowledge(ShiftHandover $handover): ShiftHandover
    {
        return DB::transaction(function () use ($handover): ShiftHandover {
            $locked = ShiftHandover::query()->lockForUpdate()->findOrFail($handover->id);
            if ($locked->status !== ShiftHandoverStatus::HandedOver) {
                throw ValidationException::withMessages(['handover' => 'Hanya handover yang sudah dikirim dapat diterima.']);
            }
            $locked->update([
                'status' => ShiftHandoverStatus::Acknowledged,
                'acknowledged_at' => now(),
            ]);
            $this->auditLogger->log('acknowledged', 'shift_handover', $locked, ['status' => ShiftHandoverStatus::HandedOver->value], [
                'status' => ShiftHandoverStatus::Acknowledged->value,
            ]);

            return $locked->refresh()->load(['creator', 'recipient', 'items']);
        }, 3);
    }
}
