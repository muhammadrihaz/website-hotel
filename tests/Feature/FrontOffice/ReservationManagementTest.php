<?php

namespace Tests\Feature\FrontOffice;

use App\Domains\Reservation\Actions\SaveReservation;
use App\Domains\Reservation\Enums\BookingSource;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Livewire\ReservationsManager;
use App\Domains\Reservation\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesFrontOfficeData;
use Tests\TestCase;

class ReservationManagementTest extends TestCase
{
    use CreatesFrontOfficeData, RefreshDatabase;

    public function test_reservation_is_created_with_safe_number_and_calculated_totals(): void
    {
        $this->actingAs(User::factory()->create());
        $guest = $this->createGuest();
        $room = $this->createRoom();

        $reservation = app(SaveReservation::class)->execute($this->payload($guest->id, $room->id, $room->room_type_id));

        $this->assertMatchesRegularExpression('/^RES-\d{8}-\d{4}$/', $reservation->reservation_number);
        $this->assertSame('500000.00', $reservation->total_room_amount);
        $this->assertSame('525000.00', $reservation->total_amount);
        $this->assertSame('PAID', $reservation->payment_status->value);
        $this->assertSame('100000.00', $reservation->security_deposit_amount);
        $this->assertDatabaseHas('reservation_status_histories', [
            'reservation_id' => $reservation->id,
            'to_status' => ReservationStatus::Confirmed->value,
        ]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'reservation', 'action' => 'created']);
    }

    public function test_overlapping_reservation_is_rejected_and_adjacent_stay_is_allowed(): void
    {
        $this->actingAs(User::factory()->create());
        $room = $this->createRoom();
        $firstGuest = $this->createGuest();
        $secondGuest = $this->createGuest();
        $action = app(SaveReservation::class);

        $action->execute($this->payload($firstGuest->id, $room->id, $room->room_type_id));

        try {
            $action->execute($this->payload($secondGuest->id, $room->id, $room->room_type_id, [
                'check_in_date' => today()->addDay()->toDateString(),
                'check_out_date' => today()->addDays(3)->toDateString(),
            ]));
            $this->fail('Overlapping reservation should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('tidak tersedia', $exception->errors()['roomId'][0]);
            $this->assertStringContainsString('RES-', $exception->errors()['roomId'][0]);
        }

        $adjacent = $action->execute($this->payload($secondGuest->id, $room->id, $room->room_type_id, [
            'check_in_date' => today()->addDays(2)->toDateString(),
            'check_out_date' => today()->addDays(4)->toDateString(),
        ]));

        $this->assertDatabaseCount('reservations', 2);
        $this->assertInstanceOf(Reservation::class, $adjacent);
    }

    public function test_confirmed_reservation_requires_full_room_payment_and_keeps_security_deposit_separate(): void
    {
        $this->actingAs(User::factory()->create());
        $guest = $this->createGuest();
        $room = $this->createRoom();
        $action = app(SaveReservation::class);

        try {
            $action->execute($this->payload($guest->id, $room->id, $room->room_type_id, [
                'paid_amount' => '100000',
                'security_deposit_amount' => '150000',
            ]));
            $this->fail('Confirmed reservation should require full room payment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('paidAmount', $exception->errors());
            $this->assertStringContainsString('wajib lunas', $exception->errors()['paidAmount'][0]);
        }

        $pending = $action->execute($this->payload($guest->id, $room->id, $room->room_type_id, [
            'paid_amount' => '100000',
            'security_deposit_amount' => '150000',
            'reservation_status' => ReservationStatus::Pending->value,
        ]));

        $this->assertSame('PARTIAL', $pending->payment_status->value);
        $this->assertSame('100000.00', $pending->paid_amount);
        $this->assertSame('150000.00', $pending->security_deposit_amount);
        $this->assertSame('425000.00', number_format((float) $pending->total_amount - (float) $pending->paid_amount, 2, '.', ''));
    }

    public function test_livewire_shows_advisory_availability_and_still_creates_reservation(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('reservation.create', 'web');
        $user->givePermissionTo('reservation.create');
        $guest = $this->createGuest();
        $room = $this->createRoom();

        Livewire::actingAs($user)->test(ReservationsManager::class)
            ->set('guestId', (string) $guest->id)
            ->set('roomTypeId', (string) $room->room_type_id)
            ->set('roomRate', '250000')
            ->set('roomId', (string) $room->id)
            ->assertSet('availabilityMessage', "Room {$room->room_number} tersedia untuk periode ini.")
            ->set('reservationStatus', ReservationStatus::Confirmed->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reservations', ['guest_id' => $guest->id, 'room_id' => $room->id]);
    }

    public function test_confirmed_reservation_can_be_cancelled_with_reason_and_room_is_released(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('reservation.cancel', 'web');
        $user->givePermissionTo('reservation.cancel');
        $this->actingAs($user);
        $guest = $this->createGuest();
        $room = $this->createRoom();
        $reservation = app(SaveReservation::class)->execute($this->payload($guest->id, $room->id, $room->room_type_id));

        $this->assertSame('RESERVED', $room->fresh()->occupancy_status->value);

        Livewire::actingAs($user)->test(ReservationsManager::class)
            ->set("cancellationReasons.{$reservation->id}", 'Guest changed travel plan')
            ->call('cancel', $reservation->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'reservation_status' => ReservationStatus::Cancelled->value,
            'cancellation_reason' => 'Guest changed travel plan',
        ]);
        $this->assertSame('VACANT', $room->fresh()->occupancy_status->value);
        $this->assertDatabaseHas('audit_logs', ['record_id' => (string) $reservation->id, 'action' => 'cancelled']);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(int $guestId, int $roomId, int $roomTypeId, array $overrides = []): array
    {
        return array_merge([
            'guest_id' => $guestId,
            'room_type_id' => $roomTypeId,
            'room_id' => $roomId,
            'booking_source' => BookingSource::Direct->value,
            'booking_reference' => null,
            'check_in_date' => today()->toDateString(),
            'check_out_date' => today()->addDays(2)->toDateString(),
            'adult_count' => 2,
            'child_count' => 0,
            'room_rate' => '250000',
            'additional_charge' => '50000',
            'discount' => '25000',
            'paid_amount' => '525000',
            'security_deposit_amount' => '100000',
            'reservation_status' => ReservationStatus::Confirmed->value,
            'special_request' => null,
            'internal_note' => null,
        ], $overrides);
    }
}
