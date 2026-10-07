<?php

namespace App\Domains\LostFound\Actions;

use App\Domains\LostFound\Enums\LostFoundStatus;
use App\Domains\LostFound\Models\LostFoundItem;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\System\Actions\AuditLogger;
use App\Domains\System\Actions\NumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveLostFoundItem
{
    public function __construct(
        private readonly NumberGenerator $numberGenerator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data): LostFoundItem
    {
        return DB::transaction(function () use ($data): LostFoundItem {
            $reservation = filled($data['reservation_id'] ?? null)
                ? Reservation::query()->findOrFail((int) $data['reservation_id'])
                : null;
            $guestId = filled($data['guest_id'] ?? null) ? (int) $data['guest_id'] : $reservation?->guest_id;
            if ($reservation && $guestId && $reservation->guest_id !== $guestId) {
                throw ValidationException::withMessages(['guestId' => 'Guest tidak sesuai dengan reservasi yang dipilih.']);
            }

            $item = LostFoundItem::query()->create([
                'code' => $this->numberGenerator->generate('LF'),
                'item_name' => trim($data['item_name']),
                'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                'found_location' => trim($data['found_location']),
                'found_date' => $data['found_date'],
                'found_by' => auth()->id(),
                'guest_id' => $guestId,
                'reservation_id' => $reservation?->id,
                'photo' => $data['photo'] ?? null,
                'storage_location' => filled($data['storage_location'] ?? null) ? trim($data['storage_location']) : null,
                'status' => LostFoundStatus::Found,
                'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
            ]);

            $this->auditLogger->log('created', 'lost_found', $item, [], [
                'code' => $item->code,
                'item_name' => $item->item_name,
                'status' => $item->status->value,
                'guest_id' => $item->guest_id,
            ]);

            return $item->refresh()->load(['guest', 'reservation', 'finder']);
        }, 3);
    }
}
