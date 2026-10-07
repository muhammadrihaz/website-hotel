<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-hotel-700">Current Stays</p><h2 class="mt-1 text-2xl font-black text-slate-950">In-House Guests</h2><p class="mt-1 text-sm text-slate-500">Daftar guest yang sedang menempati kamar.</p></div><input wire:model.live.debounce.300ms="search" class="form-input sm:w-80" placeholder="Cari guest, room, reservation..."></div>
    <div class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
        @forelse($stays as $reservation)
            @php($isLate = $reservation->check_out_date->isBefore(today(config('app.timezone'))))
            <article wire:key="inhouse-{{ $reservation->id }}" class="panel p-5">
                <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wider text-hotel-700">Room {{ $reservation->room->room_number }} · Lantai {{ $reservation->room->floor }}</p><h3 class="mt-1 text-lg font-black text-slate-950">{{ $reservation->guest->full_name }}</h3><p class="text-sm text-slate-500">{{ $reservation->reservation_number }}</p></div><span class="badge {{ $isLate ? 'bg-rose-100 text-rose-700' : 'bg-indigo-100 text-indigo-700' }}">{{ $isLate ? 'Overstay' : 'In-House' }}</span></div>
                <dl class="mt-5 grid grid-cols-2 gap-3 text-sm"><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Checked in</dt><dd class="mt-1 font-semibold">{{ $reservation->stay?->checked_in_at?->format('d M H:i') ?? '—' }}</dd></div><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Expected out</dt><dd class="mt-1 font-semibold {{ $isLate ? 'text-rose-700' : '' }}">{{ $reservation->check_out_date->format('d M Y') }}</dd></div><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Payment</dt><dd class="mt-1 font-semibold">{{ $reservation->payment_status->label() }}</dd></div><div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Telepon</dt><dd class="mt-1 font-semibold">{{ $reservation->guest->phone ?: '—' }}</dd></div></dl>
                @if($reservation->special_request)<p class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">{{ $reservation->special_request }}</p>@endif
            </article>
        @empty
            <div class="panel col-span-full px-6 py-16 text-center text-sm text-slate-500">Belum ada guest in-house.</div>
        @endforelse
    </div>
    @if($stays->hasPages())<div class="mt-5">{{ $stays->links() }}</div>@endif
</div>

