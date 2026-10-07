<?php

namespace App\Domains\Reporting\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class ReportDateRange
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $timezone = config('app.timezone');
        $end = isset($validated['end_date'])
            ? CarbonImmutable::parse($validated['end_date'], $timezone)->startOfDay()
            : CarbonImmutable::today($timezone);
        $start = isset($validated['start_date'])
            ? CarbonImmutable::parse($validated['start_date'], $timezone)->startOfDay()
            : $end->subDays(29);

        if ($start->greaterThan($end)) {
            throw ValidationException::withMessages([
                'start_date' => 'Tanggal mulai harus sebelum atau sama dengan tanggal selesai.',
            ]);
        }

        if ($start->diffInDays($end) > 365) {
            throw ValidationException::withMessages([
                'start_date' => 'Rentang laporan maksimal 366 hari.',
            ]);
        }

        return new self($start, $end);
    }

    /** @return list<CarbonImmutable> */
    public function dates(): array
    {
        $dates = [];

        for ($date = $this->start; $date->lessThanOrEqualTo($this->end); $date = $date->addDay()) {
            $dates[] = $date;
        }

        return $dates;
    }

    public function startOfDay(): CarbonImmutable
    {
        return $this->start->startOfDay();
    }

    public function endOfDay(): CarbonImmutable
    {
        return $this->end->endOfDay();
    }

    /** @return array{start_date: string, end_date: string} */
    public function query(): array
    {
        return [
            'start_date' => $this->start->toDateString(),
            'end_date' => $this->end->toDateString(),
        ];
    }

    public function label(): string
    {
        return $this->start->format('d/m/Y').' - '.$this->end->format('d/m/Y');
    }
}
