<?php

namespace Database\Seeders;

use App\Domains\Guest\Actions\SaveGuest;
use App\Domains\Guest\Enums\GuestGender;
use App\Domains\Guest\Enums\IdentityType;
use App\Domains\Guest\Models\Guest;
use App\Domains\Reservation\Actions\CheckInReservation;
use App\Domains\Reservation\Actions\SaveReservation;
use App\Domains\Reservation\Enums\BookingSource;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Models\Room;
use Illuminate\Database\Seeder;

class DemoFrontOfficeSeeder extends Seeder
{
    public function run(): void
    {
        $guestOne = $this->guest([
            'full_name' => 'Andi Pratama',
            'gender' => GuestGender::Male->value,
            'phone' => '081234567801',
            'email' => 'andi.demo@example.test',
            'identity_type' => IdentityType::Ktp->value,
            'identity_number' => '157100000001',
            'nationality' => 'Indonesia',
            'address' => 'Jambi',
            'notes' => 'Demo expected arrival.',
            'is_blacklisted' => false,
        ]);
        $guestTwo = $this->guest([
            'full_name' => 'Siti Rahma',
            'gender' => GuestGender::Female->value,
            'phone' => '081234567802',
            'email' => 'siti.demo@example.test',
            'identity_type' => IdentityType::Ktp->value,
            'identity_number' => '157100000002',
            'nationality' => 'Indonesia',
            'address' => 'Muaro Jambi',
            'notes' => 'Demo in-house guest.',
            'is_blacklisted' => false,
        ]);

        $room101 = Room::query()->with('roomType')->where('room_number', '101')->firstOrFail();
        $room102 = Room::query()->with('roomType')->where('room_number', '102')->firstOrFail();

        if (! Reservation::query()->where('booking_reference', 'DEMO-ARRIVAL')->exists()) {
            app(SaveReservation::class)->execute([
                'guest_id' => $guestOne->id,
                'room_type_id' => $room101->room_type_id,
                'room_id' => $room101->id,
                'booking_source' => BookingSource::RedDoorz->value,
                'booking_reference' => 'DEMO-ARRIVAL',
                'check_in_date' => today(config('app.timezone'))->toDateString(),
                'check_out_date' => today(config('app.timezone'))->addDays(2)->toDateString(),
                'adult_count' => 2,
                'child_count' => 0,
                'room_rate' => $room101->base_rate,
                'additional_charge' => '0',
                'discount' => '0',
                'paid_amount' => (string) ((float) $room101->base_rate * 2),
                'security_deposit_amount' => '100000',
                'reservation_status' => ReservationStatus::Confirmed->value,
                'special_request' => 'Late arrival sekitar pukul 21:00.',
                'internal_note' => 'Data demo Phase 2.',
            ]);
        }

        if (! Reservation::query()->where('booking_reference', 'DEMO-INHOUSE')->exists()) {
            $inHouse = app(SaveReservation::class)->execute([
                'guest_id' => $guestTwo->id,
                'room_type_id' => $room102->room_type_id,
                'room_id' => $room102->id,
                'booking_source' => BookingSource::WalkIn->value,
                'booking_reference' => 'DEMO-INHOUSE',
                'check_in_date' => today(config('app.timezone'))->subDay()->toDateString(),
                'check_out_date' => today(config('app.timezone'))->addDay()->toDateString(),
                'adult_count' => 1,
                'child_count' => 0,
                'room_rate' => $room102->base_rate,
                'additional_charge' => '0',
                'discount' => '0',
                'paid_amount' => (string) ((float) $room102->base_rate * 2),
                'security_deposit_amount' => '100000',
                'reservation_status' => ReservationStatus::Confirmed->value,
                'special_request' => null,
                'internal_note' => 'Data demo Phase 2.',
            ]);
            app(CheckInReservation::class)->execute($inHouse, $room102->id);
        }
    }

    /** @param array<string, mixed> $data */
    private function guest(array $data): Guest
    {
        $existing = Guest::query()->where('email_normalized', mb_strtolower($data['email']))->first();

        return $existing ?? app(SaveGuest::class)->execute($data, null, true);
    }
}
