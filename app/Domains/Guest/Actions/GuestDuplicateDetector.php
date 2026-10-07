<?php

namespace App\Domains\Guest\Actions;

use App\Domains\Guest\Models\Guest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class GuestDuplicateDetector
{
    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, Guest>
     */
    public function detect(array $data, ?Guest $except = null): Collection
    {
        $phone = $this->normalizePhone($data['phone'] ?? null);
        $email = $this->normalizeEmail($data['email'] ?? null);
        $identity = $this->normalizeIdentity($data['identity_number'] ?? null);

        if ($phone === null && $email === null && $identity === null) {
            return new Collection;
        }

        return Guest::query()
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->where(function ($query) use ($phone, $email, $identity): void {
                if ($phone !== null) {
                    $query->orWhere('phone_normalized', $phone);
                }
                if ($email !== null) {
                    $query->orWhere('email_normalized', $email);
                }
                if ($identity !== null) {
                    $query->orWhere('identity_number_normalized', $identity);
                }
            })
            ->orderBy('full_name')
            ->limit(5)
            ->get(['id', 'guest_code', 'full_name', 'phone', 'email', 'identity_number']);
    }

    public function normalizePhone(mixed $value): ?string
    {
        $normalized = preg_replace('/\D+/', '', trim((string) $value));

        return filled($normalized) ? $normalized : null;
    }

    public function normalizeEmail(mixed $value): ?string
    {
        $normalized = Str::lower(trim((string) $value));

        return filled($normalized) ? $normalized : null;
    }

    public function normalizeIdentity(mixed $value): ?string
    {
        $normalized = preg_replace('/[^A-Z0-9]+/', '', Str::upper(trim((string) $value)));

        return filled($normalized) ? $normalized : null;
    }
}
