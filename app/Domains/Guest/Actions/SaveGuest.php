<?php

namespace App\Domains\Guest\Actions;

use App\Domains\Guest\Models\Guest;
use App\Domains\System\Actions\AuditLogger;
use App\Domains\System\Actions\NumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveGuest
{
    public function __construct(
        private readonly GuestDuplicateDetector $duplicateDetector,
        private readonly NumberGenerator $numberGenerator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, ?Guest $guest = null, bool $allowDuplicate = false): Guest
    {
        return DB::transaction(function () use ($data, $guest, $allowDuplicate): Guest {
            $guest = $guest?->exists
                ? Guest::query()->lockForUpdate()->findOrFail($guest->getKey())
                : new Guest;

            $duplicates = $this->duplicateDetector->detect($data, $guest->exists ? $guest : null);
            if ($duplicates->isNotEmpty() && ! $allowDuplicate) {
                $matches = $duplicates->map(fn (Guest $match): string => "{$match->guest_code} - {$match->full_name}")->join(', ');
                throw ValidationException::withMessages([
                    'duplicate' => "Data serupa ditemukan: {$matches}. Tinjau lalu konfirmasi jika tetap ingin menyimpan.",
                ]);
            }

            $oldValues = $guest->exists ? $guest->only($this->auditedFields()) : [];
            if (! $guest->exists) {
                $guest->guest_code = $this->numberGenerator->generate('GST');
            }

            $guest->fill([
                ...$data,
                'phone_normalized' => $this->duplicateDetector->normalizePhone($data['phone'] ?? null),
                'email_normalized' => $this->duplicateDetector->normalizeEmail($data['email'] ?? null),
                'identity_number_normalized' => $this->duplicateDetector->normalizeIdentity($data['identity_number'] ?? null),
            ])->save();

            $this->auditLogger->log(
                $oldValues === [] ? 'created' : 'updated',
                'guest',
                $guest,
                $oldValues,
                $guest->only($this->auditedFields()),
            );

            return $guest->refresh();
        });
    }

    /** @return list<string> */
    private function auditedFields(): array
    {
        return ['guest_code', 'full_name', 'gender', 'phone', 'email', 'identity_type', 'nationality', 'is_blacklisted'];
    }
}
