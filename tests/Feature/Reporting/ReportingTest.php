<?php

namespace Tests\Feature\Reporting;

use App\Domains\Reporting\Services\ReportService;
use App\Domains\Reporting\Support\ReportDateRange;
use App\Domains\Reservation\Enums\BookingSource;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFrontOfficeData;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use CreatesFrontOfficeData, RefreshDatabase;

    public function test_reporting_routes_require_report_view_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $routes = [
            'reports.occupancy',
            'reports.revenue',
            'reports.reservations',
            'reports.housekeeping',
            'reports.maintenance',
            'reports.inventory',
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }

        $user->givePermissionTo('report.view');

        foreach ($routes as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_revenue_and_occupancy_reports_use_real_reservation_values(): void
    {
        $guest = $this->createGuest();
        $room = $this->createRoom();
        $this->createReservation($guest, $room, [
            'booking_source' => BookingSource::WalkIn,
            'check_in_date' => today(config('app.timezone'))->toDateString(),
            'check_out_date' => today(config('app.timezone'))->addDays(2)->toDateString(),
            'total_room_amount' => '500000.00',
            'additional_charge' => '50000.00',
            'discount' => '20000.00',
            'paid_amount' => '200000.00',
            'security_deposit_amount' => '100000.00',
            'total_amount' => '530000.00',
            'reservation_status' => ReservationStatus::Confirmed,
        ]);
        $today = CarbonImmutable::today(config('app.timezone'));
        $range = new ReportDateRange($today, $today);

        $revenue = app(ReportService::class)->revenue($range);
        $occupancy = app(ReportService::class)->occupancy($range);

        $this->assertSame(530000.0, $revenue['summary']['net_revenue']);
        $this->assertSame(200000.0, $revenue['summary']['payment']);
        $this->assertSame(100000.0, $revenue['summary']['security_deposit']);
        $this->assertSame(330000.0, $revenue['summary']['outstanding']);
        $this->assertSame(1, $occupancy['summary']['sold_room_nights']);
        $this->assertSame(100.0, $occupancy['summary']['average_occupancy']);
    }

    public function test_report_export_requires_export_permission_and_returns_excel_compatible_csv(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('report.view');
        $url = route('reports.export', [
            'report' => 'revenue',
            'start_date' => today(config('app.timezone'))->toDateString(),
            'end_date' => today(config('app.timezone'))->toDateString(),
        ]);

        $this->actingAs($user)->get($url)->assertForbidden();

        $user->givePermissionTo('report.export');
        $response = $this->actingAs($user)->get($url);

        $response->assertOk()->assertDownload('revenue-'.today(config('app.timezone'))->toDateString().'-'.today(config('app.timezone'))->toDateString().'.csv');
        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->streamedContent());
        $this->assertStringContainsString('Net Revenue', $response->streamedContent());
    }

    public function test_report_date_filter_rejects_invalid_and_excessive_ranges(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('report.view');

        $this->actingAs($user)->get(route('reports.revenue', [
            'start_date' => '2026-10-08',
            'end_date' => '2026-10-07',
        ]))->assertSessionHasErrors('start_date');

        $this->actingAs($user)->get(route('reports.revenue', [
            'start_date' => '2025-01-01',
            'end_date' => '2026-10-07',
        ]))->assertSessionHasErrors('start_date');
    }
}
