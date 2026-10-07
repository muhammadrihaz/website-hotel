<div>
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-hotel-700">Live Operations</p><h2 class="mt-1 text-2xl font-black text-slate-950">Room Board</h2><p class="mt-1 text-sm text-slate-500">Klik kartu untuk melihat guest, periode stay, booking source, dan housekeeping.</p></div>
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
            <input wire:model.live.debounce.250ms="search" class="form-input" placeholder="Nomor kamar...">
            <select wire:model.live="statusFilter" class="form-input"><option value="">Semua status</option><optgroup label="Occupancy"><option value="VACANT">Vacant</option><option value="RESERVED">Reserved</option><option value="OCCUPIED">Occupied</option></optgroup><optgroup label="Housekeeping"><option value="DIRTY">Dirty</option><option value="CLEANING">Cleaning</option><option value="CLEAN">Clean</option><option value="READY">Ready</option></optgroup><optgroup label="Operational"><option value="AVAILABLE">Available</option><option value="MAINTENANCE">Maintenance</option><option value="OUT_OF_ORDER">Out of order</option></optgroup></select>
            <select wire:model.live="floorFilter" class="form-input"><option value="">Semua lantai</option>@foreach($floors as $floor)<option value="{{ $floor }}">Lantai {{ $floor }}</option>@endforeach</select>
            <select wire:model.live="typeFilter" class="form-input"><option value="">Semua tipe</option>@foreach($roomTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap gap-2 text-xs"><span class="badge bg-emerald-100 text-emerald-700">Ready / Available</span><span class="badge bg-indigo-100 text-indigo-700">Occupied</span><span class="badge bg-sky-100 text-sky-700">Reserved</span><span class="badge bg-amber-100 text-amber-700">Dirty / Cleaning</span><span class="badge bg-rose-100 text-rose-700">Maintenance</span></div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6">
        @forelse($rooms as $room)
            @php
                $isBlocked = $room->operational_status->value !== 'AVAILABLE';
                $cardTone = match(true) { $isBlocked => 'border-rose-200 bg-rose-50/70', $room->occupancy_status->value === 'OCCUPIED' => 'border-indigo-200 bg-indigo-50/70', $room->occupancy_status->value === 'RESERVED' => 'border-sky-200 bg-sky-50/70', in_array($room->housekeeping_status->value, ['DIRTY', 'CLEANING'], true) => 'border-amber-200 bg-amber-50/70', default => 'border-emerald-200 bg-emerald-50/70' };
            @endphp
            <button wire:key="room-board-{{ $room->id }}" wire:click="showRoom({{ $room->id }})" class="rounded-2xl border p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $cardTone }}">
                <div class="flex items-start justify-between gap-3"><div><p class="text-3xl font-black text-slate-950">{{ $room->room_number }}</p><p class="text-xs font-medium text-slate-500">Lantai {{ $room->floor }} · {{ $room->roomType->code }}</p></div><span class="h-3 w-3 rounded-full {{ $isBlocked ? 'bg-rose-500' : ($room->occupancy_status->value === 'OCCUPIED' ? 'bg-indigo-500' : 'bg-emerald-500') }}"></span></div>
                <div class="mt-5 flex flex-wrap gap-1.5"><span class="badge bg-white/80 text-slate-700">{{ $room->occupancy_status->label() }}</span><span class="badge bg-white/80 text-slate-700">{{ $room->housekeeping_status->label() }}</span>@if($isBlocked)<span class="badge bg-rose-600 text-white">{{ $room->operational_status->label() }}</span>@endif</div>
                <div class="mt-4 min-h-10 border-t border-slate-900/5 pt-3">@if($room->activeStay)<p class="truncate text-sm font-bold text-slate-900">{{ $room->activeStay->guest->full_name }}</p><p class="text-xs text-slate-500">s/d {{ $room->activeStay->reservation->check_out_date->format('d M') }}</p>@else<p class="text-sm font-medium text-slate-500">Tidak ada guest aktif</p>@endif</div>
            </button>
        @empty
            <div class="panel col-span-full px-6 py-16 text-center text-sm text-slate-500">Tidak ada kamar yang cocok dengan filter.</div>
        @endforelse
    </div>

    @if($selectedRoom)
        <div class="fixed inset-0 z-[60] flex justify-end bg-slate-950/50" wire:click.self="closeRoom">
            <aside class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-wider text-hotel-700">{{ $selectedRoom->roomType->name }} · Lantai {{ $selectedRoom->floor }}</p><h3 class="mt-1 text-4xl font-black text-slate-950">Room {{ $selectedRoom->room_number }}</h3></div><button wire:click="closeRoom" class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-bold text-slate-600">Tutup</button></div>
                <div class="mt-6 grid grid-cols-3 gap-2 text-center text-xs font-semibold"><div class="rounded-xl bg-slate-100 p-3">{{ $selectedRoom->occupancy_status->label() }}</div><div class="rounded-xl bg-slate-100 p-3">{{ $selectedRoom->housekeeping_status->label() }}</div><div class="rounded-xl bg-slate-100 p-3">{{ $selectedRoom->operational_status->label() }}</div></div>

                <section class="mt-6 rounded-2xl border border-slate-200 p-5">
                    <h4 class="font-bold text-slate-900">Current guest</h4>
                    @if($selectedRoom->activeStay)
                        @php($activeReservation = $selectedRoom->activeStay->reservation)
                        <p class="mt-3 text-lg font-black text-slate-950">{{ $selectedRoom->activeStay->guest->full_name }}</p><p class="text-sm text-slate-500">{{ $activeReservation->reservation_number }} · {{ $activeReservation->booking_source->label() }}</p>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Check-in</dt><dd class="mt-1 font-semibold">{{ $selectedRoom->activeStay->checked_in_at->format('d M Y H:i') }}</dd></div><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Expected out</dt><dd class="mt-1 font-semibold">{{ $activeReservation->check_out_date->format('d M Y') }}</dd></div><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Payment</dt><dd class="mt-1 font-semibold">{{ $activeReservation->payment_status->label() }}</dd></div><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Telepon</dt><dd class="mt-1 font-semibold">{{ $selectedRoom->activeStay->guest->phone ?: '—' }}</dd></div></dl>
                    @else
                        <p class="mt-3 text-sm text-slate-500">Tidak ada guest yang sedang in-house di kamar ini.</p>
                    @endif
                </section>

                <section class="mt-5"><h4 class="font-bold text-slate-900">Active & upcoming reservations</h4><div class="mt-3 space-y-2">@forelse($selectedRoom->reservations as $reservation)<div class="rounded-xl bg-slate-50 p-3"><div class="flex items-center justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ $reservation->guest->full_name }}</p><p class="text-xs text-slate-500">{{ $reservation->reservation_number }} · {{ $reservation->check_in_date->format('d M') }} – {{ $reservation->check_out_date->format('d M') }}</p></div><span class="badge bg-white text-slate-700">{{ $reservation->reservation_status->label() }}</span></div></div>@empty<div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Tidak ada reservasi aktif.</div>@endforelse</div></section>
                @if($selectedRoom->notes)<div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="text-xs font-bold uppercase text-amber-700">Catatan kamar</p><p class="mt-1 text-sm text-amber-900">{{ $selectedRoom->notes }}</p></div>@endif
            </aside>
        </div>
    @endif
</div>

