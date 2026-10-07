<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-hotel-700">Departures</p><h2 class="mt-1 text-2xl font-black text-slate-950">Check-Out</h2><p class="mt-1 text-sm text-slate-500">Checkout menutup stay, membuat kamar DIRTY, dan membuat task housekeeping.</p></div><input wire:model.live.debounce.300ms="search" class="form-input sm:w-80" placeholder="Cari guest, room, reservation..."></div>
    <x-alert-notification />
    @error('checkout')<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>@enderror
    @error('folioReviewed')<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>@enderror

    <div class="space-y-4">
        @forelse($departures as $reservation)
            @php($dueToday = $reservation->check_out_date->isToday())
            <article wire:key="departure-{{ $reservation->id }}" class="panel p-5">
                <div class="grid gap-5 lg:grid-cols-[1.2fr_.8fr_1fr_auto] lg:items-center">
                    <div><div class="flex flex-wrap items-center gap-2"><p class="font-black text-slate-950">Room {{ $reservation->room->room_number }}</p><span class="badge {{ $dueToday ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">{{ $dueToday ? 'Due today' : 'In-House' }}</span></div><p class="mt-1 text-lg font-bold text-slate-900">{{ $reservation->guest->full_name }}</p><p class="text-sm text-slate-500">{{ $reservation->reservation_number }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Expected out</p><p class="mt-1 font-bold text-slate-800">{{ $reservation->check_out_date->format('d M Y') }}</p><p class="text-xs text-slate-500">Masuk {{ $reservation->stay?->checked_in_at?->format('d M H:i') }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Ringkasan</p><p class="mt-1 font-bold text-slate-900">Rp{{ number_format((float) $reservation->total_amount, 0, ',', '.') }}</p><p class="text-xs font-semibold {{ $reservation->payment_status->value === 'PAID' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $reservation->payment_status->label() }} · Dibayar Rp{{ number_format((float) $reservation->paid_amount, 0, ',', '.') }}</p><p class="text-xs font-semibold text-sky-700">Deposit jaminan Rp{{ number_format((float) $reservation->security_deposit_amount, 0, ',', '.') }}</p></div>
                    <div class="min-w-56"><label class="mb-3 flex items-start gap-2 rounded-xl bg-slate-50 p-3 text-xs font-medium text-slate-700"><input wire:model="folioReviewed.{{ $reservation->id }}" type="checkbox" class="mt-0.5 rounded border-slate-300 text-hotel-700"><span>Folio dan status pembayaran sudah diperiksa</span></label><button wire:click="checkOut({{ $reservation->id }})" data-confirm="Stay akan ditutup, kamar ditandai DIRTY, dan task housekeeping dibuat secara otomatis." data-confirm-title="Selesaikan check-out?" data-confirm-label="Ya, check-out" data-confirm-tone="warning" class="btn-primary w-full">Check-Out</button></div>
                </div>
            </article>
        @empty
            <div class="panel px-6 py-16 text-center"><p class="font-bold text-slate-800">Tidak ada guest yang dapat check-out.</p><p class="mt-1 text-sm text-slate-500">Guest berstatus Checked-In akan muncul di sini.</p></div>
        @endforelse
    </div>
    @if($departures->hasPages())<div class="mt-5">{{ $departures->links() }}</div>@endif
</div>
