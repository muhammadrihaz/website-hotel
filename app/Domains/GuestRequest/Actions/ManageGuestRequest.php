<?php

namespace App\Domains\GuestRequest\Actions;

use App\Domains\GuestRequest\Enums\GuestRequestStatus;
use App\Domains\GuestRequest\Models\GuestRequest;
use App\Domains\System\Actions\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageGuestRequest
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function assign(GuestRequest $request, int $userId): GuestRequest
    {
        return $this->transition($request, [GuestRequestStatus::Open, GuestRequestStatus::Assigned], GuestRequestStatus::Assigned, [
            'assigned_to' => $userId,
            'assigned_at' => now(),
        ], 'assigned');
    }

    public function start(GuestRequest $request, int $userId): GuestRequest
    {
        return $this->transition($request, [GuestRequestStatus::Open, GuestRequestStatus::Assigned], GuestRequestStatus::InProgress, [
            'assigned_to' => $request->assigned_to ?: $userId,
            'assigned_at' => $request->assigned_at ?: now(),
            'started_at' => $request->started_at ?: now(),
        ], 'started');
    }

    public function complete(GuestRequest $request, ?string $notes = null): GuestRequest
    {
        return $this->transition($request, [GuestRequestStatus::Assigned, GuestRequestStatus::InProgress], GuestRequestStatus::Completed, [
            'completed_at' => now(),
            'notes' => filled($notes) ? trim($notes) : $request->notes,
        ], 'completed');
    }

    public function cancel(GuestRequest $request, string $reason): GuestRequest
    {
        if (mb_strlen(trim($reason)) < 3) {
            throw ValidationException::withMessages(['guestRequest' => 'Alasan pembatalan minimal 3 karakter.']);
        }

        return $this->transition($request, [GuestRequestStatus::Open, GuestRequestStatus::Assigned, GuestRequestStatus::InProgress], GuestRequestStatus::Cancelled, [
            'completed_at' => now(),
            'notes' => trim($reason),
        ], 'cancelled');
    }

    /**
     * @param  list<GuestRequestStatus>  $allowed
     * @param  array<string, mixed>  $values
     */
    private function transition(GuestRequest $request, array $allowed, GuestRequestStatus $target, array $values, string $action): GuestRequest
    {
        return DB::transaction(function () use ($request, $allowed, $target, $values, $action): GuestRequest {
            $locked = GuestRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! in_array($locked->status, $allowed, true)) {
                throw ValidationException::withMessages(['guestRequest' => "Request tidak dapat diubah menjadi {$target->label()} dari status saat ini."]);
            }

            $oldStatus = $locked->status;
            $locked->update([...$values, 'status' => $target]);
            $this->auditLogger->log($action, 'guest_request', $locked, ['status' => $oldStatus->value], [
                'status' => $locked->status->value,
                'assigned_to' => $locked->assigned_to,
                'response_minutes' => $locked->responseMinutes(),
            ]);

            return $locked->refresh()->load(['guest', 'room', 'reservation', 'assignee']);
        }, 3);
    }
}
