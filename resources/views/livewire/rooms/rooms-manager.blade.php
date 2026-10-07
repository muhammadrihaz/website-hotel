<div>
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-950">Room Master</h2>
            <p class="mt-1 text-sm text-slate-500">Status kamar dipisahkan untuk mencegah state operasional ambigu.</p>
        </div>
        <div class="grid gap-2 sm:grid-cols-3">
            <input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Nomor kamar…">
            <select wire:model.live="floorFilter" class="form-input"><option value="">Semua lantai</option>@foreach($floors as $availableFloor)<option value="{{ $availableFloor }}">Lantai {{ $availableFloor }}</option>@endforeach</select>
            <select wire:model.live="typeFilter" class="form-input"><option value="">Semua tipe</option>@foreach($roomTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>
        </div>
    </div>

    <x-alert-notification />
    @error('room')<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>@enderror

    <div class="grid items-start gap-6 2xl:grid-cols-[390px_1fr]">
        @canany(['room.create', 'room.update'])
            <form wire:submit="save" class="panel p-5">
                <div class="mb-5 flex items-center justify-between"><h3 class="font-bold text-slate-900">{{ $roomId ? 'Edit kamar' : 'Kamar baru' }}</h3>@if($roomId)<button type="button" wire:click="resetForm" class="text-sm font-semibold text-slate-500">Batal</button>@endif</div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">Nomor kamar</label><input wire:model="roomNumber" class="form-input" placeholder="101">@error('roomNumber')<p class="form-error">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label">Lantai</label><input wire:model="floor" type="number" min="0" class="form-input">@error('floor')<p class="form-error">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label class="form-label">Tipe kamar</label><select wire:model="roomTypeId" class="form-input"><option value="">Pilih tipe</option>@foreach($roomTypes as $type)<option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>@endforeach</select>@error('roomTypeId')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div><label class="form-label">Tarif dasar</label><x-currency-input model="baseRate" :value="$baseRate" />@error('baseRate')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">Dewasa</label><input wire:model="capacityAdult" type="number" min="1" class="form-input">@error('capacityAdult')<p class="form-error">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label">Anak</label><input wire:model="capacityChild" type="number" min="0" class="form-input">@error('capacityChild')<p class="form-error">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3 2xl:grid-cols-1">
                        <div><label class="form-label">Occupancy</label><select wire:model="occupancyStatus" class="form-input">@foreach($occupancyStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select>@error('occupancyStatus')<p class="form-error">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label">Housekeeping</label><select wire:model="housekeepingStatus" class="form-input">@foreach($housekeepingStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select>@error('housekeepingStatus')<p class="form-error">{{ $message }}</p>@enderror</div>
                        <div><label class="form-label">Operational</label><select wire:model="operationalStatus" class="form-input">@foreach($operationalStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select>@error('operationalStatus')<p class="form-error">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label class="form-label">Deskripsi</label><textarea wire:model="description" rows="2" class="form-input"></textarea>@error('description')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div><label class="form-label">Catatan internal</label><textarea wire:model="notes" rows="2" class="form-input"></textarea>@error('notes')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <label class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-3 text-sm font-medium text-slate-700"><input wire:model="isActive" type="checkbox" class="rounded border-slate-300 text-hotel-700 focus:ring-hotel-500">Kamar aktif</label>
                    <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $roomId ? 'Simpan perubahan' : 'Buat kamar' }}</span><span wire:loading wire:target="save">Menyimpan…</span></button>
                </div>
            </form>
        @endcanany

        <section class="panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Kamar</th><th class="px-5 py-3">Tipe & Tarif</th><th class="px-5 py-3">Occupancy</th><th class="px-5 py-3">Housekeeping</th><th class="px-5 py-3">Operational</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rooms as $room)
                            @php
                                $occupancyTone = match($room->occupancy_status->value) { 'OCCUPIED' => 'bg-indigo-100 text-indigo-700', 'RESERVED' => 'bg-sky-100 text-sky-700', default => 'bg-slate-100 text-slate-700' };
                                $housekeepingTone = match($room->housekeeping_status->value) { 'READY' => 'bg-emerald-100 text-emerald-700', 'DIRTY' => 'bg-amber-100 text-amber-700', 'CLEANING' => 'bg-blue-100 text-blue-700', default => 'bg-teal-100 text-teal-700' };
                                $operationalTone = $room->operational_status->value === 'AVAILABLE' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700';
                            @endphp
                            <tr wire:key="room-{{ $room->id }}" class="hover:bg-slate-50/70">
                                <td class="px-5 py-4"><p class="text-lg font-black text-slate-950">{{ $room->room_number }}</p><p class="text-xs text-slate-500">Lantai {{ $room->floor }}{{ $room->is_active ? '' : ' · Inactive' }}</p></td>
                                <td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $room->roomType->name }}</p><p class="text-xs text-slate-500">Rp{{ number_format((float) $room->base_rate, 0, ',', '.') }}</p></td>
                                <td class="px-5 py-4"><span class="badge {{ $occupancyTone }}">{{ $room->occupancy_status->label() }}</span></td>
                                <td class="px-5 py-4"><span class="badge {{ $housekeepingTone }}">{{ $room->housekeeping_status->label() }}</span></td>
                                <td class="px-5 py-4"><span class="badge {{ $operationalTone }}">{{ $room->operational_status->label() }}</span></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">@can('update', $room)<button wire:click="edit({{ $room->id }})" class="px-2 py-1 font-semibold text-hotel-700">Edit</button>@endcan @can('delete', $room)<button wire:click="delete({{ $room->id }})" data-confirm="Room {{ $room->room_number }} akan dihapus dari master kamar. Kamar aktif atau yang memiliki riwayat operasional mungkin tidak dapat dihapus." data-confirm-title="Hapus Room {{ $room->room_number }}?" data-confirm-label="Ya, hapus kamar" data-confirm-tone="danger" class="px-2 py-1 font-semibold text-rose-600">Hapus</button>@endcan</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">Belum ada kamar yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rooms->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $rooms->links() }}</div>@endif
        </section>
    </div>
</div>
