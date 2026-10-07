<?php

namespace App\Providers;

use App\Domains\Guest\Livewire\GuestsManager;
use App\Domains\Guest\Models\Guest;
use App\Domains\GuestRequest\Livewire\GuestRequestsBoard;
use App\Domains\Housekeeping\Livewire\HousekeepingBoard;
use App\Domains\LostFound\Livewire\LostFoundBoard;
use App\Domains\Maintenance\Livewire\MaintenanceBoard;
use App\Domains\Reservation\Livewire\CheckInBoard;
use App\Domains\Reservation\Livewire\CheckOutBoard;
use App\Domains\Reservation\Livewire\InHouseBoard;
use App\Domains\Reservation\Livewire\ReservationsManager;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Livewire\RoomBoard;
use App\Domains\Room\Livewire\RoomsManager;
use App\Domains\Room\Livewire\RoomTypesManager;
use App\Domains\Room\Models\Room;
use App\Domains\Room\Models\RoomType;
use App\Domains\ShiftHandover\Livewire\ShiftHandoverBoard;
use App\Domains\System\Livewire\AuditLogViewer;
use App\Domains\System\Livewire\RolesManager;
use App\Domains\System\Livewire\UsersManager;
use App\Models\User;
use App\Policies\GuestPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\RoomPolicy;
use App\Policies\RoomTypePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Guest::class, GuestPolicy::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);
        Gate::policy(RoomType::class, RoomTypePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Livewire::component('guests.guests-manager', GuestsManager::class);
        Livewire::component('reservations.reservations-manager', ReservationsManager::class);
        Livewire::component('reservations.check-in-board', CheckInBoard::class);
        Livewire::component('reservations.in-house-board', InHouseBoard::class);
        Livewire::component('reservations.check-out-board', CheckOutBoard::class);
        Livewire::component('rooms.room-board', RoomBoard::class);
        Livewire::component('housekeeping.housekeeping-board', HousekeepingBoard::class);
        Livewire::component('maintenance.maintenance-board', MaintenanceBoard::class);
        Livewire::component('guest-requests.guest-requests-board', GuestRequestsBoard::class);
        Livewire::component('lost-found.lost-found-board', LostFoundBoard::class);
        Livewire::component('shift-handover.shift-handover-board', ShiftHandoverBoard::class);
        Livewire::component('rooms.room-types-manager', RoomTypesManager::class);
        Livewire::component('rooms.rooms-manager', RoomsManager::class);
        Livewire::component('system.users-manager', UsersManager::class);
        Livewire::component('system.roles-manager', RolesManager::class);
        Livewire::component('system.audit-log-viewer', AuditLogViewer::class);
    }
}
