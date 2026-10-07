<?php

namespace App\Domains\Dashboard\Http\Controllers;

use App\Domains\GuestRequest\Enums\GuestRequestStatus;
use App\Domains\GuestRequest\Models\GuestRequest;
use App\Domains\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domains\Housekeeping\Models\HousekeepingTask;
use App\Domains\Maintenance\Enums\MaintenanceStatus;
use App\Domains\Maintenance\Models\MaintenanceTicket;
use App\Domains\Reservation\Enums\PaymentStatus;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Models\Room;
use App\Domains\Room\Models\RoomType;
use App\Domains\ShiftHandover\Enums\HandoverItemStatus;
use App\Domains\ShiftHandover\Models\ShiftHandoverItem;
use App\Domains\System\Models\AuditLog;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $roomMetrics = Room::query()
            ->where('is_active', true)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN occupancy_status = 'OCCUPIED' THEN 1 ELSE 0 END) AS occupied")
            ->selectRaw("SUM(CASE WHEN occupancy_status = 'VACANT' AND housekeeping_status = 'READY' AND operational_status = 'AVAILABLE' THEN 1 ELSE 0 END) AS available")
            ->selectRaw("SUM(CASE WHEN housekeeping_status = 'DIRTY' THEN 1 ELSE 0 END) AS dirty")
            ->selectRaw("SUM(CASE WHEN housekeeping_status = 'CLEANING' THEN 1 ELSE 0 END) AS cleaning")
            ->selectRaw("SUM(CASE WHEN operational_status IN ('MAINTENANCE', 'OUT_OF_ORDER') THEN 1 ELSE 0 END) AS maintenance")
            ->first();

        $today = today(config('app.timezone'))->toDateString();
        $reservationMetrics = Reservation::query()
            ->selectRaw('SUM(CASE WHEN reservation_status = ? AND check_in_date = ? THEN 1 ELSE 0 END) AS expected_arrivals', [ReservationStatus::Confirmed->value, $today])
            ->selectRaw('SUM(CASE WHEN reservation_status = ? AND check_out_date = ? THEN 1 ELSE 0 END) AS expected_departures', [ReservationStatus::CheckedIn->value, $today])
            ->selectRaw('SUM(CASE WHEN checked_in_at IS NOT NULL AND DATE(checked_in_at) = ? THEN 1 ELSE 0 END) AS checkins_today', [$today])
            ->selectRaw('SUM(CASE WHEN checked_out_at IS NOT NULL AND DATE(checked_out_at) = ? THEN 1 ELSE 0 END) AS checkouts_today', [$today])
            ->selectRaw('SUM(CASE WHEN payment_status IN (?, ?) AND reservation_status NOT IN (?, ?) THEN total_amount - paid_amount ELSE 0 END) AS outstanding', [PaymentStatus::Unpaid->value, PaymentStatus::Partial->value, ReservationStatus::Cancelled->value, ReservationStatus::NoShow->value])
            ->first();

        return view('dashboard.index', [
            'roomMetrics' => $roomMetrics,
            'reservationMetrics' => $reservationMetrics,
            'roomTypeCount' => RoomType::query()->where('is_active', true)->count(),
            'activeUserCount' => User::query()->where('is_active', true)->count(),
            'recentActivities' => AuditLog::query()->with('user:id,name')->latest()->limit(8)->get(),
            'operationsMetrics' => [
                'housekeeping' => HousekeepingTask::query()->where('status', '!=', HousekeepingTaskStatus::Verified->value)->count(),
                'maintenance' => MaintenanceTicket::query()->whereIn('status', array_map(fn (MaintenanceStatus $status): string => $status->value, MaintenanceStatus::blocking()))->count(),
                'guest_requests' => GuestRequest::query()->whereIn('status', [GuestRequestStatus::Open->value, GuestRequestStatus::Assigned->value, GuestRequestStatus::InProgress->value])->count(),
                'handover_items' => ShiftHandoverItem::query()->where('status', '!=', HandoverItemStatus::Completed->value)->count(),
            ],
        ]);
    }
}
