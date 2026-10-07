<div class="flex h-full flex-col">
    <div class="border-b border-white/10 px-6 py-5">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-hotel-500 text-lg font-black text-white">D</div>
            <div class="min-w-0">
                <p class="truncate font-bold text-white">{{ config('hotel.property.short_name') }}</p>
                <p class="text-xs text-slate-500">by RedDoorz</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-scrollbar flex-1 space-y-7 overflow-y-auto px-4 py-6" aria-label="Navigasi utama">
        @can('dashboard.view')
            <div><x-nav-link route="dashboard" label="Dashboard" /></div>
        @endcan

        @if(auth()->user()->canAny(['reservation.view', 'room.view', 'checkin.execute', 'checkout.execute', 'guest.view']))
            <div>
                <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-600">Front Office</p>
                <div class="space-y-1">
                    @can('reservation.view')<x-nav-link route="front-office.reservations.index" label="Reservations" />@endcan
                    @can('room.view')<x-nav-link route="front-office.room-board.index" label="Room Board" />@endcan
                    @can('checkin.execute')<x-nav-link route="front-office.check-in.index" label="Check-In" />@endcan
                    @can('reservation.view')<x-nav-link route="front-office.in-house.index" label="In-House" />@endcan
                    @can('checkout.execute')<x-nav-link route="front-office.check-out.index" label="Check-Out" />@endcan
                    @can('guest.view')<x-nav-link route="front-office.guests.index" label="Guests" />@endcan
                </div>
            </div>
        @endif

        @if(auth()->user()->canAny(['housekeeping.view', 'guest_request.view', 'maintenance.view', 'lost_found.view']))
            <div>
                <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-600">Operations</p>
                <div class="space-y-1">
                    @can('housekeeping.view')<x-nav-link route="operations.housekeeping.index" label="Housekeeping" />@endcan
                    @can('guest_request.view')<x-nav-link route="operations.guest-requests.index" label="Guest Requests" />@endcan
                    @can('maintenance.view')<x-nav-link route="operations.maintenance.index" label="Maintenance" />@endcan
                    @can('lost_found.view')<x-nav-link route="operations.lost-found.index" label="Lost & Found" />@endcan
                </div>
            </div>
        @endif

        @can('shift_handover.view')
            <div>
                <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-600">Staff</p>
                <div class="space-y-1"><x-nav-link route="staff.shift-handover.index" label="Shift Handover" /></div>
            </div>
        @endcan

        @can('report.view')
            <div>
                <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-600">Reports</p>
                <div class="space-y-1">
                    <x-nav-link route="reports.occupancy" label="Occupancy" />
                    <x-nav-link route="reports.revenue" label="Revenue" />
                    <x-nav-link route="reports.reservations" label="Reservations" />
                    <x-nav-link route="reports.housekeeping" label="Housekeeping" />
                    <x-nav-link route="reports.maintenance" label="Maintenance" />
                    <x-nav-link route="reports.inventory" label="Inventory" />
                </div>
            </div>
        @endcan

        @if(auth()->user()->canAny(['user.view', 'role.view', 'room.view', 'room_type.view', 'audit.view']))
            <div>
                <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-600">System</p>
                <div class="space-y-1">
                    @can('user.view')<x-nav-link route="system.users.index" label="Users" />@endcan
                    @can('role.view')<x-nav-link route="system.roles.index" label="Roles & Permissions" />@endcan
                    @can('room.view')<x-nav-link route="system.rooms.index" label="Room Master" />@endcan
                    @can('room_type.view')<x-nav-link route="system.room-types.index" label="Room Types" />@endcan
                    @can('audit.view')<x-nav-link route="system.audit-logs.index" label="Audit Logs" />@endcan
                </div>
            </div>
        @endif
    </nav>

    <div class="border-t border-white/10 px-6 py-4 text-xs text-slate-600">Hotel Operations System</div>
</div>
