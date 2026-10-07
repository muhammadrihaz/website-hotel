<?php

namespace App\Domains\GuestRequest\Actions;

use App\Domains\GuestRequest\Enums\GuestRequestStatus;
use App\Domains\GuestRequest\Models\GuestRequest;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\System\Actions\AuditLogger;
use App\Domains\System\Actions\NumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveGuestRequest
{
    public function __construct(
        private readonly NumberGenerator $numberGenerator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data): GuestRequest
    {
        return DB::transaction(function () use ($data): GuestRequest {
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($data['reservation_id']);
            if ($reservation->reservation_status !== ReservationStatus::CheckedIn || ! $reservation->room_id) {
                throw ValidationException::withMessages(['reservationId' => 'Guest request hanya dapat dibuat untuk guest yang sedang in-house.']);
            }

            $assignedTo = filled($data['assigned_to'] ?? null) ? (int) $data['assigned_to'] : null;
            $request = GuestRequest::query()->create([
                'request_number' => $this->numberGenerator->generate('GR'),
                'guest_id' => $reservation->guest_id,
                'reservation_id' => $reservation->id,
                'room_id' => $reservation->room_id,
                'category' => $data['category'],
                'description' => trim($data['description']),
                'priority' => $data['priority'],
                'assigned_department' => $data['assigned_department'],
                'assigned_to' => $assignedTo,
                'status' => $assignedTo ? GuestRequestStatus::Assigned : GuestRequestStatus::Open,
                'requested_at' => now(),
                'assigned_at' => $assignedTo ? now() : null,
                'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
            ]);

            $this->auditLogger->log('created', 'guest_request', $request, [], [
                'request_number' => $request->request_number,
                'room_id' => $request->room_id,
                'category' => $request->category->value,
                'priority' => $request->priority->value,
                'status' => $request->status->value,
            ]);

            return $request->refresh()->load(['guest', 'room', 'reservation', 'assignee']);
        }, 3);
    }
}
