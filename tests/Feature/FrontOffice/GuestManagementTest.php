<?php

namespace Tests\Feature\FrontOffice;

use App\Domains\Guest\Livewire\GuestsManager;
use App\Domains\Reservation\Actions\SaveReservation;
use App\Domains\Reservation\Enums\BookingSource;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesFrontOfficeData;
use Tests\TestCase;

class GuestManagementTest extends TestCase
{
    use CreatesFrontOfficeData, RefreshDatabase;

    public function test_duplicate_guest_requires_explicit_confirmation(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('guest.create', 'web');
        $user->givePermissionTo('guest.create');
        $this->createGuest([
            'full_name' => 'Existing Guest',
            'phone' => '0812-3456-7890',
            'phone_normalized' => '081234567890',
        ]);

        $component = Livewire::actingAs($user)->test(GuestsManager::class)
            ->set('fullName', 'Possible Duplicate')
            ->set('phone', '0812 3456 7890')
            ->set('nationality', 'Indonesia')
            ->call('save')
            ->assertHasErrors('duplicate');

        $this->assertDatabaseMissing('guests', ['full_name' => 'Possible Duplicate']);

        $component->set('duplicateConfirmed', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('guests', [
            'full_name' => 'Possible Duplicate',
            'phone_normalized' => '081234567890',
        ]);
    }

    public function test_blacklisted_guest_cannot_receive_new_reservation(): void
    {
        $guest = $this->createGuest(['is_blacklisted' => true]);
        $room = $this->createRoom();

        $this->expectException(ValidationException::class);
        app(SaveReservation::class)->execute([
            'guest_id' => $guest->id,
            'room_type_id' => $room->room_type_id,
            'room_id' => $room->id,
            'booking_source' => BookingSource::Direct->value,
            'check_in_date' => today()->toDateString(),
            'check_out_date' => today()->addDay()->toDateString(),
            'adult_count' => 1,
            'child_count' => 0,
            'room_rate' => '250000',
            'additional_charge' => '0',
            'discount' => '0',
            'paid_amount' => '250000',
            'security_deposit_amount' => '100000',
            'reservation_status' => ReservationStatus::Confirmed->value,
        ]);
    }
}
