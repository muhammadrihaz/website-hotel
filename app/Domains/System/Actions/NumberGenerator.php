<?php

namespace App\Domains\System\Actions;

use App\Domains\System\Models\DocumentSequence;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NumberGenerator
{
    public function generate(string $documentType, ?CarbonInterface $date = null): string
    {
        $documentType = strtoupper(trim($documentType));
        if (! preg_match('/^[A-Z0-9]{2,20}$/', $documentType)) {
            throw new InvalidArgumentException('Invalid document type.');
        }

        $date ??= now(config('app.timezone'));
        $businessDate = $date->toDateString();

        return DB::transaction(function () use ($documentType, $businessDate, $date): string {
            DocumentSequence::query()->insertOrIgnore([
                'document_type' => $documentType,
                'sequence_date' => $businessDate,
                'current_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DocumentSequence::query()
                ->where('document_type', $documentType)
                ->whereDate('sequence_date', $businessDate)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('current_value');
            $value = (int) $sequence->fresh()->current_value;

            return sprintf('%s-%s-%04d', $documentType, $date->format('Ymd'), $value);
        });
    }
}
