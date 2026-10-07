<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domains\Housekeeping\Models\HousekeepingTask;
use App\Domains\Maintenance\Enums\MaintenanceCategory;
use App\Domains\Maintenance\Enums\MaintenanceStatus;
use App\Domains\Maintenance\Models\MaintenanceTicket;
use App\Domains\Reporting\Support\ReportDateRange;
use App\Domains\Reservation\Enums\BookingSource;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class ReportService
{
    /** @return array<string, mixed> */
    public function occupancy(ReportDateRange $range): array
    {
        $totalRooms = Room::query()->where('is_active', true)->count();
        $reservations = Reservation::query()
            ->select(['id', 'room_id', 'check_in_date', 'check_out_date', 'reservation_status'])
            ->whereNotNull('room_id')
            ->whereIn('reservation_status', [
                ReservationStatus::Confirmed->value,
                ReservationStatus::CheckedIn->value,
                ReservationStatus::CheckedOut->value,
            ])
            ->whereDate('check_in_date', '<=', $range->end->toDateString())
            ->whereDate('check_out_date', '>', $range->start->toDateString())
            ->get();

        $daily = collect($range->dates())->map(function (CarbonImmutable $date) use ($reservations, $totalRooms): array {
            $occupied = $reservations
                ->filter(fn (Reservation $reservation): bool => $reservation->check_in_date->lessThanOrEqualTo($date)
                    && $reservation->check_out_date->greaterThan($date))
                ->pluck('room_id')
                ->unique()
                ->count();
            $available = max($totalRooms - $occupied, 0);
            $percentage = $totalRooms > 0 ? round(($occupied / $totalRooms) * 100, 1) : 0.0;

            return [
                'date' => $date->toDateString(),
                'date_label' => $date->translatedFormat('d M'),
                'available' => $available,
                'occupied' => $occupied,
                'percentage' => $percentage,
            ];
        });

        $peak = $daily->sortByDesc('percentage')->first();

        return [
            'summary' => [
                'total_rooms' => $totalRooms,
                'room_nights' => $totalRooms * $daily->count(),
                'sold_room_nights' => $daily->sum('occupied'),
                'available_room_nights' => $daily->sum('available'),
                'average_occupancy' => round((float) $daily->avg('percentage'), 1),
                'peak' => $peak,
            ],
            'daily' => $daily,
        ];
    }

    /** @return array<string, mixed> */
    public function revenue(ReportDateRange $range): array
    {
        $reservations = Reservation::query()
            ->select([
                'id', 'check_in_date', 'total_room_amount', 'additional_charge', 'discount',
                'paid_amount', 'security_deposit_amount', 'total_amount', 'reservation_status',
            ])
            ->whereDate('check_in_date', '>=', $range->start->toDateString())
            ->whereDate('check_in_date', '<=', $range->end->toDateString())
            ->whereNotIn('reservation_status', [
                ReservationStatus::Cancelled->value,
                ReservationStatus::NoShow->value,
            ])
            ->get();
        $byDate = $reservations->groupBy(fn (Reservation $reservation): string => $reservation->check_in_date->toDateString());

        $daily = collect($range->dates())->map(function (CarbonImmutable $date) use ($byDate): array {
            /** @var Collection<int, Reservation> $items */
            $items = $byDate->get($date->toDateString(), collect());
            $roomRevenue = (float) $items->sum(fn (Reservation $item): float => (float) $item->total_room_amount);
            $additionalRevenue = (float) $items->sum(fn (Reservation $item): float => (float) $item->additional_charge);
            $discount = (float) $items->sum(fn (Reservation $item): float => (float) $item->discount);
            $netRevenue = (float) $items->sum(fn (Reservation $item): float => (float) $item->total_amount);
            $payment = (float) $items->sum(fn (Reservation $item): float => (float) $item->paid_amount);
            $securityDeposit = (float) $items->sum(fn (Reservation $item): float => (float) $item->security_deposit_amount);

            return [
                'date' => $date->toDateString(),
                'date_label' => $date->translatedFormat('d M'),
                'reservations' => $items->count(),
                'room_revenue' => $roomRevenue,
                'additional_revenue' => $additionalRevenue,
                'discount' => $discount,
                'net_revenue' => $netRevenue,
                'payment' => $payment,
                'security_deposit' => $securityDeposit,
                'outstanding' => max($netRevenue - $payment, 0),
            ];
        });
        $netRevenue = (float) $daily->sum('net_revenue');

        return [
            'summary' => [
                'reservations' => $reservations->count(),
                'room_revenue' => (float) $daily->sum('room_revenue'),
                'additional_revenue' => (float) $daily->sum('additional_revenue'),
                'discount' => (float) $daily->sum('discount'),
                'net_revenue' => $netRevenue,
                'payment' => (float) $daily->sum('payment'),
                'security_deposit' => (float) $daily->sum('security_deposit'),
                'outstanding' => (float) $daily->sum('outstanding'),
                'average_value' => $reservations->isNotEmpty() ? round($netRevenue / $reservations->count(), 2) : 0.0,
            ],
            'daily' => $daily,
        ];
    }

    /** @return array<string, mixed> */
    public function reservations(ReportDateRange $range): array
    {
        $reservations = Reservation::query()
            ->select(['id', 'booking_source', 'reservation_status', 'created_at'])
            ->whereBetween('created_at', [$range->startOfDay(), $range->endOfDay()])
            ->get();

        $sources = $reservations
            ->groupBy(fn (Reservation $reservation): string => $reservation->booking_source->value)
            ->map(function (Collection $items, string $source): array {
                $bookingSource = BookingSource::tryFrom($source);

                return [
                    'source' => $source,
                    'label' => $bookingSource?->label() ?? str($source)->headline()->toString(),
                    'total' => $items->count(),
                    'confirmed' => $items->where('reservation_status', ReservationStatus::Confirmed)->count(),
                    'checked_in' => $items->whereIn('reservation_status', [ReservationStatus::CheckedIn, ReservationStatus::CheckedOut])->count(),
                    'cancelled' => $items->where('reservation_status', ReservationStatus::Cancelled)->count(),
                    'no_show' => $items->where('reservation_status', ReservationStatus::NoShow)->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $checkIns = Reservation::query()
            ->whereBetween('checked_in_at', [$range->startOfDay(), $range->endOfDay()])
            ->count();

        return [
            'summary' => [
                'total' => $reservations->count(),
                'confirmed' => $reservations->where('reservation_status', ReservationStatus::Confirmed)->count(),
                'check_ins' => $checkIns,
                'cancelled' => $reservations->where('reservation_status', ReservationStatus::Cancelled)->count(),
                'no_show' => $reservations->where('reservation_status', ReservationStatus::NoShow)->count(),
                'cancellation_rate' => $reservations->isNotEmpty()
                    ? round(($reservations->where('reservation_status', ReservationStatus::Cancelled)->count() / $reservations->count()) * 100, 1)
                    : 0.0,
            ],
            'sources' => $sources,
        ];
    }

    /** @return array<string, mixed> */
    public function housekeeping(ReportDateRange $range): array
    {
        $created = HousekeepingTask::query()
            ->whereBetween('created_at', [$range->startOfDay(), $range->endOfDay()])
            ->get();
        $completed = HousekeepingTask::query()
            ->with('assignee:id,name')
            ->whereBetween('completed_at', [$range->startOfDay(), $range->endOfDay()])
            ->get();
        $durationItems = $completed->filter(fn (HousekeepingTask $task): bool => $task->started_at !== null && $task->completed_at !== null);

        $productivity = $completed
            ->groupBy(fn (HousekeepingTask $task): string => $task->assignee?->name ?? 'Belum ditugaskan')
            ->map(function (Collection $items, string $staff): array {
                $durations = $items
                    ->filter(fn (HousekeepingTask $task): bool => $task->started_at !== null && $task->completed_at !== null)
                    ->map(fn (HousekeepingTask $task): int => (int) round($task->started_at->diffInMinutes($task->completed_at)));

                return [
                    'staff' => $staff,
                    'rooms_cleaned' => $items->count(),
                    'average_minutes' => $durations->isNotEmpty() ? (int) round($durations->avg()) : null,
                    'verified' => $items->where('status', HousekeepingTaskStatus::Verified)->count(),
                ];
            })
            ->sortByDesc('rooms_cleaned')
            ->values();

        $daily = collect($range->dates())->map(function (CarbonImmutable $date) use ($created, $completed): array {
            return [
                'date' => $date->toDateString(),
                'date_label' => $date->translatedFormat('d M'),
                'created' => $created->filter(fn (HousekeepingTask $task): bool => $task->created_at->isSameDay($date))->count(),
                'completed' => $completed->filter(fn (HousekeepingTask $task): bool => $task->completed_at?->isSameDay($date) ?? false)->count(),
            ];
        });

        return [
            'summary' => [
                'tasks_created' => $created->count(),
                'rooms_cleaned' => $completed->count(),
                'verified' => $completed->where('status', HousekeepingTaskStatus::Verified)->count(),
                'average_minutes' => $durationItems->isNotEmpty()
                    ? (int) round($durationItems->avg(fn (HousekeepingTask $task): float => $task->started_at->diffInMinutes($task->completed_at)))
                    : null,
                'active_now' => HousekeepingTask::query()
                    ->whereNotIn('status', [HousekeepingTaskStatus::Verified->value])
                    ->count(),
            ],
            'daily' => $daily,
            'productivity' => $productivity,
        ];
    }

    /** @return array<string, mixed> */
    public function maintenance(ReportDateRange $range): array
    {
        $tickets = MaintenanceTicket::query()
            ->with(['room:id,room_number'])
            ->whereBetween('created_at', [$range->startOfDay(), $range->endOfDay()])
            ->get();
        $resolved = MaintenanceTicket::query()
            ->whereBetween('resolved_at', [$range->startOfDay(), $range->endOfDay()])
            ->get();
        $durations = $resolved
            ->filter(fn (MaintenanceTicket $ticket): bool => $ticket->resolved_at !== null)
            ->map(fn (MaintenanceTicket $ticket): float => $ticket->created_at->diffInMinutes($ticket->resolved_at) / 60);

        $categories = $tickets
            ->groupBy(fn (MaintenanceTicket $ticket): string => $ticket->category->value)
            ->map(function (Collection $items, string $category): array {
                $enum = MaintenanceCategory::tryFrom($category);

                return [
                    'category' => $enum?->label() ?? str($category)->headline()->toString(),
                    'tickets' => $items->count(),
                    'open' => $items->filter(fn (MaintenanceTicket $ticket): bool => in_array($ticket->status, MaintenanceStatus::active(), true))->count(),
                    'resolved' => $items->whereIn('status', [MaintenanceStatus::Resolved, MaintenanceStatus::Verified, MaintenanceStatus::Closed])->count(),
                ];
            })
            ->sortByDesc('tickets')
            ->values();

        $repeatedRooms = $tickets
            ->whereNotNull('room_id')
            ->groupBy('room_id')
            ->filter(fn (Collection $items): bool => $items->count() > 1)
            ->map(function (Collection $items): array {
                /** @var MaintenanceTicket $first */
                $first = $items->first();

                return [
                    'room' => $first->room?->room_number ?? 'Tanpa kamar',
                    'issues' => $items->count(),
                    'open' => $items->filter(fn (MaintenanceTicket $ticket): bool => in_array($ticket->status, MaintenanceStatus::active(), true))->count(),
                ];
            })
            ->sortByDesc('issues')
            ->values();

        return [
            'summary' => [
                'tickets' => $tickets->count(),
                'resolved' => $resolved->count(),
                'open_issues' => $tickets->filter(fn (MaintenanceTicket $ticket): bool => in_array($ticket->status, MaintenanceStatus::active(), true))->count(),
                'blocking_rooms' => $tickets->where('blocks_room', true)->count(),
                'average_resolution_hours' => $durations->isNotEmpty() ? round((float) $durations->avg(), 1) : null,
                'repeat_rooms' => $repeatedRooms->count(),
            ],
            'categories' => $categories,
            'repeated_rooms' => $repeatedRooms,
        ];
    }
}
