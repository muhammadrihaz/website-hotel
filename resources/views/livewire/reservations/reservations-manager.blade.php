<div>
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-hotel-700">Front Office</p>
            <h2 class="mt-1 text-2xl font-black text-slate-950">Reservations</h2>
            <p class="mt-1 text-sm text-slate-500">Ketersediaan kamar divalidasi ulang secara transaksional saat disimpan.</p>
        </div>
        <div class="grid gap-2 sm:grid-cols-3">
            <input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Nomor / guest...">
            <select wire:model.live="statusFilter" class="form-input"><option value="">Semua status</option>@foreach($reservationStatuses as $option)<option value="{{ $option->value }}">{{ $option->label() }}</option>@endforeach</select>
            <select wire:model.live="sourceFilter" class="form-input"><option value="">Semua sumber</option>@foreach($bookingSources as $option)<option value="{{ $option->value }}">{{ $option->label() }}</option>@endforeach</select>
        </div>
    </div>

    <x-alert-notification />
    @error('reservation')<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>@enderror

    <div class="grid items-start gap-6 2xl:grid-cols-[410px_1fr]">
        @canany(['reservation.create', 'reservation.update'])
            <form wire:submit="save" class="panel p-5">
                <div class="mb-5 flex items-center justify-between"><div><h3 class="font-bold text-slate-900">{{ $reservationId ? 'Edit reservasi' : 'Reservasi baru' }}</h3><p class="text-xs text-slate-500">Pembayaran kamar dilakukan di muka, terpisah dari deposit jaminan.</p></div>@if($reservationId)<button type="button" wire:click="resetForm" class="text-sm font-semibold text-slate-500">Batal</button>@endif</div>
                <div class="space-y-4">
                    <div><label class="form-label">Guest</label><select wire:model="guestId" class="form-input"><option value="">Pilih guest</option>@foreach($guests as $guest)<option value="{{ $guest->id }}">{{ $guest->full_name }} · {{ $guest->guest_code }}</option>@endforeach</select>@error('guestId')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-2 gap-3"><div><label class="form-label">Check-in</label><input wire:model.live="checkInDate" type="date" class="form-input">@error('checkInDate')<p class="form-error">{{ $message }}</p>@enderror</div><div><label class="form-label">Check-out</label><input wire:model.live="checkOutDate" type="date" class="form-input">@error('checkOutDate')<p class="form-error">{{ $message }}</p>@enderror</div></div>
                    <div><label class="form-label">Tipe kamar</label><select wire:model.live="roomTypeId" class="form-input"><option value="">Pilih tipe</option>@foreach($roomTypes as $type)<option value="{{ $type->id }}">{{ $type->name }} · Rp{{ number_format((float) $type->base_rate, 0, ',', '.') }}</option>@endforeach</select>@error('roomTypeId')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div>
                        <label class="form-label">Alokasi kamar <span class="font-normal text-slate-400">(opsional)</span></label>
                        <select wire:model.live="roomId" class="form-input"><option value="">Alokasikan saat check-in</option>@foreach($candidateRooms as $room)<option value="{{ $room->id }}" @disabled(in_array($room->id, $unavailableRoomIds, true))>Room {{ $room->room_number }} · {{ $room->roomType->code }}{{ in_array($room->id, $unavailableRoomIds, true) ? ' · Bentrok' : '' }}</option>@endforeach</select>
                        @error('roomId')<p class="form-error">{{ $message }}</p>@enderror
                        @if($availabilityMessage)<p class="mt-1.5 text-xs font-semibold {{ str_contains($availabilityMessage, 'tersedia untuk') ? 'text-emerald-700' : 'text-rose-700' }}">{{ $availabilityMessage }}</p>@endif
                    </div>
                    <div class="grid grid-cols-2 gap-3"><div><label class="form-label">Sumber</label><select wire:model="bookingSource" class="form-input">@foreach($bookingSources as $option)<option value="{{ $option->value }}">{{ $option->label() }}</option>@endforeach</select></div><div><label class="form-label">Referensi</label><input wire:model="bookingReference" class="form-input" placeholder="OTA / chat ref"></div></div>
                    <div class="grid grid-cols-2 gap-3"><div><label class="form-label">Dewasa</label><input wire:model="adultCount" type="number" min="1" class="form-input"></div><div><label class="form-label">Anak</label><input wire:model="childCount" type="number" min="0" class="form-input"></div></div>
                    <div><label class="form-label">Tarif per malam</label><x-currency-input model="roomRate" :value="$roomRate" />@error('roomRate')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">Biaya tambahan</label><x-currency-input model="additionalCharge" :value="$additionalCharge" />@error('additionalCharge')<p class="form-error">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label">Diskon</label><x-currency-input model="discount" :value="$discount" />@error('discount')<p class="form-error">{{ $message }}</p>@enderror</div>
                    </div>
                    <section class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-sm font-black text-emerald-950">Pembayaran di muka</p><p class="mt-0.5 text-xs leading-5 text-emerald-700">Kamar wajib lunas untuk status Confirmed.</p></div>
                            <span class="badge bg-white text-emerald-700 ring-1 ring-emerald-200">{{ $this->nightCount() }} malam</span>
                        </div>
                        <dl class="mt-3 space-y-2 border-y border-emerald-200/70 py-3 text-xs">
                            <div class="flex justify-between gap-3"><dt class="text-emerald-700">Total kamar</dt><dd class="font-bold text-emerald-950">Rp{{ number_format($this->estimatedRoomTotal(), 0, ',', '.') }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="font-bold text-emerald-800">Total tagihan</dt><dd class="text-sm font-black text-emerald-950">Rp{{ number_format($this->estimatedTotal(), 0, ',', '.') }}</dd></div>
                        </dl>
                        <div class="mt-3 space-y-3">
                            <div><label class="form-label">Pembayaran kamar</label><x-currency-input model="paidAmount" :value="$paidAmount" />@error('paidAmount')<p class="form-error">{{ $message }}</p>@enderror</div>
                            <div><label class="form-label">Deposit jaminan <span class="font-normal text-slate-400">(refundable)</span></label><x-currency-input model="securityDepositAmount" :value="$securityDepositAmount" />@error('securityDepositAmount')<p class="form-error">{{ $message }}</p>@enderror<p class="mt-1.5 text-xs leading-5 text-slate-500">Default hotel Rp{{ number_format((int) config('hotel.reservation.default_security_deposit'), 0, ',', '.') }} dan dapat disesuaikan.</p></div>
                        </div>
                    </section>
                    <div><label class="form-label">Status</label><select wire:model="reservationStatus" class="form-input"><option value="PENDING">Pending</option><option value="CONFIRMED">Confirmed</option></select></div>
                    <div><label class="form-label">Permintaan khusus</label><textarea wire:model="specialRequest" rows="2" class="form-input" placeholder="Late arrival, extra pillow..."></textarea></div>
                    <div><label class="form-label">Catatan internal</label><textarea wire:model="internalNote" rows="2" class="form-input"></textarea></div>
                    <button class="btn-primary w-full" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $reservationId ? 'Simpan perubahan' : 'Buat reservasi' }}</span><span wire:loading wire:target="save">Memvalidasi & menyimpan...</span></button>
                </div>
            </form>
        @endcanany

        <section class="panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Reservasi</th><th class="px-5 py-3">Guest</th><th class="px-5 py-3">Stay</th><th class="px-5 py-3">Room</th><th class="px-5 py-3">Total</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reservations as $reservation)
                            @php
                                $statusTone = match($reservation->reservation_status->value) { 'CONFIRMED' => 'bg-sky-100 text-sky-700', 'CHECKED_IN' => 'bg-indigo-100 text-indigo-700', 'CHECKED_OUT' => 'bg-emerald-100 text-emerald-700', 'CANCELLED', 'NO_SHOW' => 'bg-rose-100 text-rose-700', default => 'bg-amber-100 text-amber-700' };
                                $paymentTone = match($reservation->payment_status->value) { 'PAID' => 'text-emerald-700', 'PARTIAL' => 'text-amber-700', default => 'text-rose-700' };
                            @endphp
                            <tr wire:key="reservation-{{ $reservation->id }}" class="align-top hover:bg-slate-50/70">
                                <td class="px-5 py-4"><p class="font-black text-slate-950">{{ $reservation->reservation_number }}</p><p class="text-xs text-slate-500">{{ $reservation->booking_source->label() }}{{ $reservation->booking_reference ? ' · '.$reservation->booking_reference : '' }}</p></td>
                                <td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $reservation->guest->full_name }}</p><p class="text-xs text-slate-500">{{ $reservation->guest->phone ?: $reservation->guest->guest_code }}</p>@if($reservation->guest->is_blacklisted)<span class="mt-1 badge bg-rose-100 text-rose-700">Blacklisted</span>@endif</td>
                                <td class="px-5 py-4"><p class="font-medium text-slate-700">{{ $reservation->check_in_date->format('d M') }} – {{ $reservation->check_out_date->format('d M Y') }}</p><p class="text-xs text-slate-500">{{ $reservation->nightCount() }} malam · {{ $reservation->adult_count }} dewasa</p></td>
                                <td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $reservation->room ? 'Room '.$reservation->room->room_number : 'Belum dialokasikan' }}</p><p class="text-xs text-slate-500">{{ $reservation->roomType->name }}</p></td>
                                <td class="px-5 py-4"><p class="font-bold text-slate-900">Rp{{ number_format((float) $reservation->total_amount, 0, ',', '.') }}</p><p class="text-xs font-semibold {{ $paymentTone }}">{{ $reservation->payment_status->label() }} · Dibayar Rp{{ number_format((float) $reservation->paid_amount, 0, ',', '.') }}</p><p class="mt-0.5 text-xs font-semibold text-sky-700">Deposit jaminan Rp{{ number_format((float) $reservation->security_deposit_amount, 0, ',', '.') }}</p></td>
                                <td class="px-5 py-4"><span class="badge {{ $statusTone }}">{{ $reservation->reservation_status->label() }}</span></td>
                                <td class="px-5 py-4 text-right">
                                    @can('update', $reservation)<button wire:click="edit({{ $reservation->id }})" class="px-2 py-1 font-semibold text-hotel-700">Edit</button>@endcan
                                    @can('cancel', $reservation)
                                        <details class="relative mt-2 text-left"><summary class="cursor-pointer list-none text-right text-xs font-semibold text-rose-600">Batalkan</summary><div class="absolute right-0 z-10 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-xl"><label class="form-label">Alasan pembatalan</label><textarea wire:model="cancellationReasons.{{ $reservation->id }}" rows="2" class="form-input" placeholder="Wajib diisi"></textarea>@error('cancellationReason')<p class="form-error">{{ $message }}</p>@enderror<button wire:click="cancel({{ $reservation->id }})" data-confirm="Reservasi {{ $reservation->reservation_number }} akan dibatalkan dan alokasi kamar akan dilepas. Pastikan alasan pembatalan sudah benar." data-confirm-title="Batalkan reservasi?" data-confirm-label="Ya, batalkan" data-confirm-tone="danger" class="btn-danger mt-2 w-full">Konfirmasi batal</button></div></details>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-14 text-center text-slate-500">Belum ada reservasi yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reservations->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $reservations->links() }}</div>@endif
        </section>
    </div>
</div>
