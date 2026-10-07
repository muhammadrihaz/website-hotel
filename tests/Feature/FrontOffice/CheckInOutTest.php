<?php

namespace Tests\Feature\FrontOffice;

use App\Domains\Reservation\Actions\CheckInReservation;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Reservation\Livewire\CheckInBoard;
use App\Domains\Reservation\Livewire\CheckOutBoard;
use App\Domains\Room\Enums\HousekeepingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesFrontOfficeData;
use Tests\TestCase;

class CheckInOutTest extends TestCase
{
    use CreatesFrontOfficeData, RefreshDatabase;

    public function test_check_in_creates_stay_and_marks_room_occupied(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('checkin.execute', 'web');
        $user->givePermissionTo('checkin.execute');
        $room = $this->createRoom();
        $reservation = $this->createReservation($this->createGuest(), $room);

        Livewire::actingAs($user)->test(CheckInBoard::class)
            ->set("roomAssignments.{$reservation->id}", $room->id)
            ->call('checkIn', $reservation->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'reservation_status' => 'CHECKED_IN']);
        $this->assertDatabaseHas('stays', ['reservation_id' => $reservation->id, 'room_id' => $room->id, 'checked_out_at' => null]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'occupancy_status' => 'OCCUPIED']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'reservation', 'action' => 'checked_in']);
    }

    public function test_checkout_requires_folio_review_then_marks_room_dirty_and_creates_task(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('checkout.execute', 'web');
        $user->givePermissionTo('checkout.execute');
        $this->actingAs($user);
        $room = $this->createRoom();
        $reservation = $this->createReservation($this->createGuest(), $room);
        app(CheckInReservation::class)->execute($reservation, $room->id);

        $component = Livewire::actingAs($user)->test(CheckOutBoard::class)
            ->call('checkOut', $reservation->id)
            ->assertHasErrors('folioReviewed');

        $component->set("folioReviewed.{$reservation->id}", true)
            ->call('checkOut', $reservation->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'reservation_status' => ReservationStatus::CheckedOut->value]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'occupancy_status' => 'VACANT', 'housekeeping_status' => 'DIRTY']);
        $this->assertDatabaseHas('housekeeping_tasks', ['reservation_id' => $reservation->id, 'room_id' => $room->id, 'status' => 'PENDING']);
        $this->assertDatabaseCount('housekeeping_checklist_items', 13);
        $this->assertDatabaseMissing('stays', ['reservation_id' => $reservation->id, 'checked_out_at' => null]);
    }

    public function test_check_in_rejects_room_that_is_not_ready(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('checkin.execute', 'web');
        $user->givePermissionTo('checkin.execute');
        $room = $this->createRoom(['housekeeping_status' => HousekeepingStatus::Dirty]);
        $reservation = $this->createReservation($this->createGuest(), $room);

        Livewire::actingAs($user)->test(CheckInBoard::class)
            ->set("roomAssignments.{$reservation->id}", $room->id)
            ->call('checkIn', $reservation->id)
            ->assertHasErrors('checkin');

        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'reservation_status' => 'CONFIRMED']);
        $this->assertDatabaseMissing('stays', ['reservation_id' => $reservation->id]);
    }
}
