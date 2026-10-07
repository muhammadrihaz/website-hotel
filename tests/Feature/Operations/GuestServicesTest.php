<?php

namespace Tests\Feature\Operations;

use App\Domains\GuestRequest\Actions\ManageGuestRequest;
use App\Domains\GuestRequest\Actions\SaveGuestRequest;
use App\Domains\GuestRequest\Enums\GuestRequestCategory;
use App\Domains\GuestRequest\Enums\GuestRequestDepartment;
use App\Domains\GuestRequest\Enums\GuestRequestPriority;
use App\Domains\GuestRequest\Enums\GuestRequestStatus;
use App\Domains\LostFound\Actions\SaveLostFoundItem;
use App\Domains\LostFound\Actions\UpdateLostFoundStatus;
use App\Domains\LostFound\Enums\LostFoundStatus;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFrontOfficeData;
use Tests\TestCase;

class GuestServicesTest extends TestCase
{
    use CreatesFrontOfficeData, RefreshDatabase;

    public function test_in_house_guest_request_records_assignment_and_response_workflow(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $room = $this->createRoom();
        $reservation = $this->createReservation($this->createGuest(), $room, [
            'reservation_status' => ReservationStatus::CheckedIn,
        ]);

        $request = app(SaveGuestRequest::class)->execute([
            'reservation_id' => $reservation->id,
            'category' => GuestRequestCategory::ExtraTowel->value,
            'priority' => GuestRequestPriority::Normal->value,
            'assigned_department' => GuestRequestDepartment::Housekeeping->value,
            'description' => 'Need two additional towels.',
        ]);
        $request = app(ManageGuestRequest::class)->assign($request, $user->id);
        $request = app(ManageGuestRequest::class)->start($request, $user->id);
        $request = app(ManageGuestRequest::class)->complete($request, 'Delivered to guest.');

        $this->assertSame(GuestRequestStatus::Completed, $request->status);
        $this->assertNotNull($request->responseMinutes());
        $this->assertDatabaseHas('audit_logs', ['module' => 'guest_request', 'action' => 'completed']);
    }

    public function test_lost_found_item_has_safe_number_and_controlled_status_history(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $item = app(SaveLostFoundItem::class)->execute([
            'item_name' => 'Silver Watch',
            'found_location' => 'Lobby',
            'found_date' => today()->toDateString(),
            'storage_location' => 'Locker B-02',
        ]);
        $this->assertMatchesRegularExpression('/^LF-\d{8}-\d{4}$/', $item->code);

        $item = app(UpdateLostFoundStatus::class)->execute($item, LostFoundStatus::Stored, 'Stored by Front Office.');
        $item = app(UpdateLostFoundStatus::class)->execute($item, LostFoundStatus::Claimed, 'Claimed with ID verification.');
        $item = app(UpdateLostFoundStatus::class)->execute($item, LostFoundStatus::Returned, 'Returned to owner.');

        $this->assertSame(LostFoundStatus::Returned, $item->status);
        $this->assertNotNull($item->resolved_at);
        $this->assertDatabaseHas('audit_logs', ['module' => 'lost_found', 'action' => 'status_updated']);
    }
}
