<?php

namespace Database\Seeders;

use App\Domains\Guest\Models\Guest;
use App\Domains\GuestRequest\Actions\SaveGuestRequest;
use App\Domains\GuestRequest\Enums\GuestRequestCategory;
use App\Domains\GuestRequest\Enums\GuestRequestDepartment;
use App\Domains\GuestRequest\Enums\GuestRequestPriority;
use App\Domains\GuestRequest\Models\GuestRequest;
use App\Domains\Housekeeping\Actions\CreateHousekeepingTask;
use App\Domains\Housekeeping\Enums\HousekeepingPriority;
use App\Domains\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domains\Housekeeping\Models\HousekeepingTask;
use App\Domains\LostFound\Actions\SaveLostFoundItem;
use App\Domains\LostFound\Models\LostFoundItem;
use App\Domains\Maintenance\Actions\SaveMaintenanceTicket;
use App\Domains\Maintenance\Enums\MaintenanceCategory;
use App\Domains\Maintenance\Enums\MaintenancePriority;
use App\Domains\Maintenance\Models\MaintenanceTicket;
use App\Domains\Reservation\Actions\CheckInReservation;
use App\Domains\Reservation\Actions\SaveReservation;
use App\Domains\Reservation\Enums\BookingSource;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Models\Room;
use App\Domains\ShiftHandover\Actions\ManageShiftHandover;
use App\Domains\ShiftHandover\Enums\HandoverItemPriority;
use App\Domains\ShiftHandover\Enums\ShiftType;
use App\Domains\ShiftHandover\Models\ShiftHandover;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $room103 = Room::query()->where('room_number', '103')->first();
        if ($room103 && ! HousekeepingTask::query()->where('room_id', $room103->id)->whereIn('status', [
            HousekeepingTaskStatus::Pending->value,
            HousekeepingTaskStatus::Assigned->value,
            HousekeepingTaskStatus::Cleaning->value,
            HousekeepingTaskStatus::Completed->value,
        ])->exists()) {
            app(CreateHousekeepingTask::class)->execute($room103, null, HousekeepingPriority::High, 'Demo Phase 3: priority cleaning.');
        }

        $room205 = Room::query()->where('room_number', '205')->first();
        if ($room205 && ! MaintenanceTicket::query()->where('description', 'Demo Phase 3: AC noise.')->exists()) {
            app(SaveMaintenanceTicket::class)->execute([
                'room_id' => $room205->id,
                'location' => null,
                'category' => MaintenanceCategory::AirConditioner->value,
                'priority' => MaintenancePriority::High->value,
                'description' => 'Demo Phase 3: AC noise.',
                'blocks_room' => true,
            ]);
        }

        $inHouse = Reservation::query()->where('reservation_status', ReservationStatus::CheckedIn->value)->first();
        if (! $inHouse && ! Reservation::query()->where('booking_reference', 'DEMO-OPS-INHOUSE')->exists()) {
            $guest = Guest::query()->first();
            $room104 = Room::query()->with('roomType')->where('room_number', '104')->first();
            if ($guest && $room104 && $room104->isReadyForSale()) {
                $inHouse = app(SaveReservation::class)->execute([
                    'guest_id' => $guest->id,
                    'room_type_id' => $room104->room_type_id,
                    'room_id' => $room104->id,
                    'booking_source' => BookingSource::Direct->value,
                    'booking_reference' => 'DEMO-OPS-INHOUSE',
                    'check_in_date' => today(config('app.timezone'))->toDateString(),
                    'check_out_date' => today(config('app.timezone'))->addDay()->toDateString(),
                    'adult_count' => 1,
                    'child_count' => 0,
                    'room_rate' => $room104->base_rate,
                    'additional_charge' => '0',
                    'discount' => '0',
                    'paid_amount' => $room104->base_rate,
                    'security_deposit_amount' => '100000',
                    'reservation_status' => ReservationStatus::Confirmed->value,
                    'special_request' => null,
                    'internal_note' => 'Data demo Phase 3.',
                ]);
                $inHouse = app(CheckInReservation::class)->execute($inHouse, $room104->id);
            }
        }
        if ($inHouse && ! GuestRequest::query()->where('description', 'Demo Phase 3: extra bath towel.')->exists()) {
            app(SaveGuestRequest::class)->execute([
                'reservation_id' => $inHouse->id,
                'category' => GuestRequestCategory::ExtraTowel->value,
                'priority' => GuestRequestPriority::Normal->value,
                'assigned_department' => GuestRequestDepartment::Housekeeping->value,
                'description' => 'Demo Phase 3: extra bath towel.',
                'notes' => 'Deliver two towels.',
            ]);
        }

        if (! LostFoundItem::query()->where('notes', 'Demo Phase 3 data.')->exists()) {
            app(SaveLostFoundItem::class)->execute([
                'item_name' => 'Phone Charger',
                'description' => 'Black USB-C charger.',
                'found_location' => 'Lobby sofa',
                'found_date' => today(config('app.timezone'))->toDateString(),
                'storage_location' => 'Front Office Locker A-01',
                'notes' => 'Demo Phase 3 data.',
            ]);
        }

        if (! ShiftHandover::query()->where('notes', 'Demo Phase 3 handover.')->exists()) {
            $action = app(ManageShiftHandover::class);
            $handover = $action->create([
                'shift_date' => today(config('app.timezone'))->toDateString(),
                'shift_type' => ShiftType::Morning->value,
                'notes' => 'Demo Phase 3 handover.',
            ]);
            $action->addItem($handover, [
                'room_id' => $room205?->id,
                'reservation_id' => null,
                'category' => 'MAINTENANCE',
                'description' => 'Follow up AC inspection Room 205.',
                'priority' => HandoverItemPriority::High->value,
            ]);
            $recipient = User::query()->where('is_active', true)->first();
            if ($recipient) {
                $action->handover($handover, $recipient->id);
            }
        }
    }
}
