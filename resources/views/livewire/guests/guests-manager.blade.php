<div>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-hotel-700">Front Office</p>
            <h2 class="mt-1 text-2xl font-black text-slate-950">Guest Management</h2>
            <p class="mt-1 text-sm text-slate-500">Identitas tamu, duplicate warning, dan riwayat menginap dalam satu profil.</p>
        </div>
        <input wire:model.live.debounce.300ms="search" class="form-input lg:w-80" placeholder="Cari nama, kode, telepon, identitas...">
    </div>

    <x-alert-notification />

    <div class="grid items-start gap-6 2xl:grid-cols-[390px_1fr]">
        @canany(['guest.create', 'guest.update'])
            <form wire:submit="save" class="panel p-5">
                <div class="mb-5 flex items-center justify-between">
                    <div><h3 class="font-bold text-slate-900">{{ $guestId ? 'Edit guest' : 'Guest baru' }}</h3><p class="text-xs text-slate-500">Field kontak dipakai untuk mendeteksi duplikat.</p></div>
                    @if($guestId)<button type="button" wire:click="resetForm" class="text-sm font-semibold text-slate-500">Batal</button>@endif
                </div>

                <div class="space-y-4">
                    <div><label class="form-label">Nama lengkap</label><input wire:model="fullName" class="form-input" autocomplete="off">@error('fullName')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">Gender</label><select wire:model="gender" class="form-input"><option value="">Tidak diisi</option>@foreach($genders as $option)<option value="{{ $option->value }}">{{ $option->label() }}</option>@endforeach</select>@error('gender')<p class="form-error">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label">Tanggal lahir</label><input wire:model="dateOfBirth" type="date" class="form-input">@error('dateOfBirth')<p class="form-error">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label class="form-label">Telepon</label><input wire:model.live.debounce.500ms="phone" class="form-input" placeholder="08...">@error('phone')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div><label class="form-label">Email</label><input wire:model.live.debounce.500ms="email" type="email" class="form-input">@error('email')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-[120px_1fr] gap-3">
                        <div><label class="form-label">Identitas</label><select wire:model="identityType" class="form-input"><option value="">Pilih</option>@foreach($identityTypes as $option)<option value="{{ $option->value }}">{{ $option->label() }}</option>@endforeach</select></div>
                        <div><label class="form-label">Nomor</label><input wire:model.live.debounce.500ms="identityNumber" class="form-input"></div>
                    </div>
                    @error('identityType')<p class="form-error">{{ $message }}</p>@enderror @error('identityNumber')<p class="form-error">{{ $message }}</p>@enderror
                    <div><label class="form-label">Kewarganegaraan</label><input wire:model="nationality" class="form-input"></div>
                    <div><label class="form-label">Alamat</label><textarea wire:model="address" rows="2" class="form-input"></textarea></div>
                    <div><label class="form-label">Catatan internal</label><textarea wire:model="notes" rows="2" class="form-input"></textarea></div>
                    <label class="flex items-start gap-3 rounded-xl border border-rose-100 bg-rose-50 px-3 py-3 text-sm text-rose-800"><input wire:model="isBlacklisted" type="checkbox" class="mt-0.5 rounded border-rose-300 text-rose-600 focus:ring-rose-500"><span><strong>Blacklist</strong><span class="block text-xs text-rose-600">Guest tidak dapat dibuatkan reservasi baru.</span></span></label>

                    @error('duplicate')
                        <div class="rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                            <p class="font-semibold">{{ $message }}</p>
                            @foreach($duplicateMatches as $match)
                                <button type="button" wire:click="showProfile({{ $match['id'] }})" class="mt-2 mr-2 rounded-lg bg-white px-2.5 py-1.5 text-xs font-semibold shadow-sm">{{ $match['guest_code'] }} · {{ $match['full_name'] }}</button>
                            @endforeach
                            <label class="mt-3 flex items-center gap-2 font-medium"><input wire:model="duplicateConfirmed" type="checkbox" class="rounded border-amber-400 text-amber-700">Saya sudah meninjau dan tetap ingin menyimpan.</label>
                        </div>
                    @enderror

                    <button class="btn-primary w-full" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $guestId ? 'Simpan perubahan' : 'Buat guest' }}</span><span wire:loading wire:target="save">Menyimpan...</span></button>
                </div>
            </form>
        @endcanany

        <section class="panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Guest</th><th class="px-5 py-3">Kontak</th><th class="px-5 py-3">Riwayat</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($guests as $guest)
                            <tr wire:key="guest-{{ $guest->id }}" class="hover:bg-slate-50/70">
                                <td class="px-5 py-4"><p class="font-bold text-slate-900">{{ $guest->full_name }}</p><p class="text-xs font-medium text-hotel-700">{{ $guest->guest_code }}</p></td>
                                <td class="px-5 py-4"><p class="text-slate-700">{{ $guest->phone ?: '—' }}</p><p class="text-xs text-slate-500">{{ $guest->email ?: 'Email belum ada' }}</p></td>
                                <td class="px-5 py-4"><p class="font-semibold text-slate-700">{{ $guest->stays_count }} stay</p><p class="text-xs text-slate-500">{{ $guest->reservations_count }} reservasi</p></td>
                                <td class="px-5 py-4"><span class="badge {{ $guest->is_blacklisted ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $guest->is_blacklisted ? 'Blacklisted' : 'Active' }}</span></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right"><button wire:click="showProfile({{ $guest->id }})" class="px-2 py-1 font-semibold text-slate-600">Profil</button>@can('update', $guest)<button wire:click="edit({{ $guest->id }})" class="px-2 py-1 font-semibold text-hotel-700">Edit</button>@endcan</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">Belum ada guest yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($guests->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $guests->links() }}</div>@endif
        </section>
    </div>

    @if($profile)
        <div class="fixed inset-0 z-[60] flex justify-end bg-slate-950/50" wire:click.self="closeProfile">
            <aside class="h-full w-full max-w-xl overflow-y-auto bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-wider text-hotel-700">{{ $profile->guest_code }}</p><h3 class="mt-1 text-2xl font-black text-slate-950">{{ $profile->full_name }}</h3></div><button wire:click="closeProfile" class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-bold text-slate-600">Tutup</button></div>
                <dl class="mt-6 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Telepon</dt><dd class="mt-1 font-semibold">{{ $profile->phone ?: '—' }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Email</dt><dd class="mt-1 break-all font-semibold">{{ $profile->email ?: '—' }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Identitas</dt><dd class="mt-1 font-semibold">{{ $profile->identity_type?->label() ?? '—' }} {{ $profile->identity_number }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">Kewarganegaraan</dt><dd class="mt-1 font-semibold">{{ $profile->nationality ?: '—' }}</dd></div>
                </dl>

                <h4 class="mt-7 font-bold text-slate-900">Reservation & stay history</h4>
                <div class="mt-3 space-y-3">
                    @forelse($profile->reservations as $reservation)
                        <div class="rounded-xl border border-slate-200 p-4"><div class="flex items-center justify-between gap-3"><div><p class="font-bold text-slate-900">{{ $reservation->reservation_number }}</p><p class="text-xs text-slate-500">{{ $reservation->check_in_date->format('d M Y') }} – {{ $reservation->check_out_date->format('d M Y') }} · {{ $reservation->room?->room_number ?? $reservation->roomType->name }}</p></div><span class="badge bg-slate-100 text-slate-700">{{ $reservation->reservation_status->label() }}</span></div></div>
                    @empty
                        <div class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">Belum ada riwayat reservasi.</div>
                    @endforelse
                </div>
                @if($profile->notes)<div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="text-xs font-bold uppercase text-amber-700">Catatan</p><p class="mt-1 text-sm text-amber-900">{{ $profile->notes }}</p></div>@endif
            </aside>
        </div>
    @endif
</div>
