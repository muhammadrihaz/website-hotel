<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-hotel-700">Arrivals</p><h2 class="mt-1 text-2xl font-black text-slate-950">Check-In</h2><p class="mt-1 text-sm text-slate-500">Hanya kamar READY, AVAILABLE, dan tanpa booking overlap yang ditampilkan.</p></div><input wire:model.live.debounce.300ms="search" class="form-input sm:w-80" placeholder="Cari reservation / guest..."></div>
    <x-alert-notification />
    @error('checkin')<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>@enderror
    @error('roomAssignments')<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>@enderror

    <div class="space-y-4">
        @forelse($arrivals as $reservation)
            <article wire:key="arrival-{{ $reservation->id }}" class="panel p-5">
                <div class="grid gap-5 lg:grid-cols-[1.2fr_1fr_1fr_auto] lg:items-center">
                    <div><div class="flex flex-wrap items-center gap-2"><p class="font-black text-slate-950">{{ $reservation->reservation_number }}</p>@if($reservation->check_in_date->format('Y-m-d') < $today)<span class="badge bg-amber-100 text-amber-700">Overdue arrival</span>@else<span class="badge bg-sky-100 text-sky-700">Arrival today</span>@endif</div><p class="mt-1 text-lg font-bold text-slate-900">{{ $reservation->guest->full_name }}</p><p class="text-sm text-slate-500">{{ $reservation->guest->phone ?: $reservation->guest->guest_code }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Stay</p><p class="mt-1 font-semibold text-slate-800">{{ $reservation->check_in_date->format('d M') }} – {{ $reservation->check_out_date->format('d M Y') }}</p><p class="text-sm text-slate-500">{{ $reservation->nightCount() }} malam · {{ $reservation->adult_count }} dewasa</p></div>
                    <div><label class="form-label">Kamar {{ $reservation->roomType->name }}</label><select wire:model="roomAssignments.{{ $reservation->id }}" class="form-input"><option value="">{{ $reservation->room ? 'Saat ini Room '.$reservation->room->room_number : 'Pilih kamar ready' }}</option>@foreach($availableRooms[$reservation->id] as $room)<option value="{{ $room->id }}">Room {{ $room->room_number }}</option>@endforeach</select><p class="mt-1 text-xs text-slate-500">{{ count($availableRooms[$reservation->id]) }} kamar memenuhi syarat</p></div>
                    <button wire:click="checkIn({{ $reservation->id }})" data-confirm="Pastikan identitas guest dan kamar yang dipilih sudah sesuai sebelum memulai stay." data-confirm-title="Lakukan check-in?" data-confirm-label="Ya, check-in" data-confirm-tone="primary" wire:loading.attr="disabled" class="btn-primary whitespace-nowrap">Check-In</button>
                </div>
                @if($reservation->special_request)<div class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900"><strong>Special request:</strong> {{ $reservation->special_request }}</div>@endif
            </article>
        @empty
            <div class="panel px-6 py-16 text-center"><p class="font-bold text-slate-800">Tidak ada expected arrival.</p><p class="mt-1 text-sm text-slate-500">Reservasi Confirmed yang sudah memasuki tanggal check-in akan muncul di sini.</p></div>
        @endforelse
    </div>
    @if($arrivals->hasPages())<div class="mt-5">{{ $arrivals->links() }}</div>@endif
</div>
