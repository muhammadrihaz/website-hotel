<?php

namespace App\Domains\Reporting\Http\Controllers;

use App\Domains\Reporting\Services\ReportService;
use App\Domains\Reporting\Support\ReportDateRange;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    /** @var list<string> */
    private const REPORTS = ['occupancy', 'revenue', 'reservations', 'housekeeping', 'maintenance', 'inventory'];

    public function show(Request $request, ReportService $reports, string $report): View
    {
        abort_unless(in_array($report, self::REPORTS, true), 404);

        $range = ReportDateRange::fromRequest($request);
        $data = match ($report) {
            'occupancy' => $reports->occupancy($range),
            'revenue' => $reports->revenue($range),
            'reservations' => $reports->reservations($range),
            'housekeeping' => $reports->housekeeping($range),
            'maintenance' => $reports->maintenance($range),
            'inventory' => ['available' => false],
        };

        return view("reports.{$report}", [
            'range' => $range,
            'reportData' => $data,
        ]);
    }

    public function export(Request $request, ReportService $reports, string $report): StreamedResponse
    {
        abort_unless(in_array($report, array_diff(self::REPORTS, ['inventory']), true), 404);

        $range = ReportDateRange::fromRequest($request);
        [$headers, $rows] = $this->exportRows($report, $reports, $range);
        $filename = sprintf('%s-%s-%s.csv', $report, $range->start->toDateString(), $range->end->toDateString());

        return response()->streamDownload(function () use ($headers, $rows): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers);

            foreach ($rows as $row) {
                fputcsv($stream, array_map($this->sanitizeCsvValue(...), $row));
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: list<string>, 1: iterable<array<int, mixed>>} */
    private function exportRows(string $report, ReportService $reports, ReportDateRange $range): array
    {
        return match ($report) {
            'occupancy' => [
                ['Tanggal', 'Kamar Tersedia', 'Kamar Terisi', 'Okupansi (%)'],
                $reports->occupancy($range)['daily']->map(fn (array $row): array => [
                    $row['date'], $row['available'], $row['occupied'], $row['percentage'],
                ]),
            ],
            'revenue' => [
                ['Tanggal Check-in', 'Reservasi', 'Room Revenue', 'Additional Revenue', 'Diskon', 'Net Revenue', 'Pembayaran Kamar', 'Deposit Jaminan', 'Saldo'],
                $reports->revenue($range)['daily']->map(fn (array $row): array => [
                    $row['date'], $row['reservations'], $row['room_revenue'], $row['additional_revenue'],
                    $row['discount'], $row['net_revenue'], $row['payment'], $row['security_deposit'], $row['outstanding'],
                ]),
            ],
            'reservations' => [
                ['Booking Source', 'Jumlah Reservasi', 'Confirmed', 'Check-in', 'Cancelled', 'No Show'],
                $reports->reservations($range)['sources']->map(fn (array $row): array => [
                    $row['label'], $row['total'], $row['confirmed'], $row['checked_in'], $row['cancelled'], $row['no_show'],
                ]),
            ],
            'housekeeping' => [
                ['Staff', 'Kamar Dibersihkan', 'Rata-rata Menit', 'Verified'],
                $reports->housekeeping($range)['productivity']->map(fn (array $row): array => [
                    $row['staff'], $row['rooms_cleaned'], $row['average_minutes'] ?? '', $row['verified'],
                ]),
            ],
            'maintenance' => [
                ['Kategori', 'Jumlah Tiket', 'Open Issues', 'Resolved'],
                $reports->maintenance($range)['categories']->map(fn (array $row): array => [
                    $row['category'], $row['tickets'], $row['open'], $row['resolved'],
                ]),
            ],
        };
    }

    private function sanitizeCsvValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'{$value}" : $value;
    }
}
