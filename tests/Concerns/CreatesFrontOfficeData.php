<?php

namespace Tests\Concerns;

use App\Domains\Guest\Models\Guest;
use App\Domains\Reservation\Enums\BookingSource;
use App\Domains\Reservation\Enums\PaymentStatus;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Enums\OccupancyStatus;
use App\Domains\Room\Enums\OperationalStatus;
use App\Domains\Room\Models\Room;
use App\Domains\Room\Models\RoomType;
use Illuminate\Support\Str;

trait CreatesFrontOfficeData
{
    protected function createGuest(array $attributes = []): Guest
    {
        $unique = Str::lower(Str::random(8));

        return Guest::query()->create(array_merge([
            'guest_code' => 'GST-TEST-'.Str::upper(Str::random(6)),
            'full_name' => 'Test Guest',
            'phone' => '0812'.$unique,
            'phone_normalized' => '0812'.$unique,
            'email' => "{$unique}@example.test",
            'email_normalized' => "{$unique}@example.test",
            'nationality' => 'Indonesia',
            'is_blacklisted' => false,
        ], $attributes));
    }

    protected function createRoom(array $attributes = []): Room
    {
        $type = RoomType::query()->create([
            'name' => 'Standard Test',
            'code' => 'STD-'.Str::upper(Str::random(4)),
            'base_rate' => 250000,
            'capacity_adult' => 2,
            'capacity_child' => 1,
            'is_active' => true,
        ]);

        return Room::query()->create(array_merge([
            'room_number' => (string) random_int(400, 999).Str::upper(Str::random(2)),
            'floor' => 4,
            'room_type_id' => $type->id,
            'occupancy_status' => OccupancyStatus::Vacant,
            'housekeeping_status' => HousekeepingStatus::Ready,
            'operational_status' => OperationalStatus::Available,
            'base_rate' => 250000,
            'capacity_adult' => 2,
            'capacity_child' => 1,
            'is_active' => true,
        ], $attributes));
    }

    protected function createReservation(Guest $guest, Room $room, array $attributes = []): Reservation
    {
        return Reservation::query()->create(array_merge([
            'reservation_number' => 'RES-TEST-'.Str::upper(Str::random(8)),
            'guest_id' => $guest->id,
            'room_type_id' => $room->room_type_id,
            'room_id' => $room->id,
            'booking_source' => BookingSource::Direct,
            'check_in_date' => today(config('app.timezone')),
            'check_out_date' => today(config('app.timezone'))->addDays(2),
            'adult_count' => 1,
            'child_count' => 0,
            'room_rate' => '250000.00',
            'total_room_amount' => '500000.00',
            'additional_charge' => '0.00',
            'discount' => '0.00',
            'paid_amount' => '500000.00',
            'security_deposit_amount' => '100000.00',
            'total_amount' => '500000.00',
            'payment_status' => PaymentStatus::Paid,
            'reservation_status' => ReservationStatus::Confirmed,
        ], $attributes));
    }
}
