<?php

namespace App\Domains\Reservation\Actions;

use Illuminate\Validation\ValidationException;

class MoneyCalculator
{
    public function toMinor(mixed $amount, string $field = 'amount'): int
    {
        $value = trim((string) $amount);
        if (! preg_match('/^\d{1,13}(?:\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages([$field => 'Nilai uang tidak valid.']);
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function fromMinor(int $amount): string
    {
        return sprintf('%d.%02d', intdiv($amount, 100), $amount % 100);
    }
}
