<div>
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-950">Tipe kamar</h2>
            <p class="mt-1 text-sm text-slate-500">Tarif dan kapasitas default tidak di-hardcode pada kamar.</p>
        </div>
        <div class="relative w-full sm:w-72">
            <input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Cari nama atau kode…">
            <div wire:loading wire:target="search" class="absolute right-3 top-3 text-xs text-slate-400">Mencari…</div>
        </div>
    </div>

    <x-alert-notification />
    @error('roomType')<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div>@enderror

    <div class="grid items-start gap-6 xl:grid-cols-[360px_1fr]">
        @canany(['room_type.create', 'room_type.update'])
            <form wire:submit="save" class="panel p-5">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900">{{ $roomTypeId ? 'Edit tipe kamar' : 'Tipe kamar baru' }}</h3>
                    @if($roomTypeId)<button type="button" wire:click="resetForm" class="text-sm font-semibold text-slate-500 hover:text-slate-800">Batal</button>@endif
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Nama</label>
                        <input wire:model="name" class="form-input" placeholder="Deluxe">
                        @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Kode</label>
                        <input wire:model="code" class="form-input uppercase" placeholder="DLX">
                        @error('code')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="form-label">Tarif dasar</label>
                        <x-currency-input model="baseRate" :value="$baseRate" />
                        @error('baseRate')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Dewasa</label>
                            <input wire:model="capacityAdult" type="number" min="1" class="form-input">
                            @error('capacityAdult')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="form-label">Anak</label>
                            <input wire:model="capacityChild" type="number" min="0" class="form-input">
                            @error('capacityChild')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Deskripsi</label>
                        <textarea wire:model="description" rows="3" class="form-input" placeholder="Fasilitas dan keterangan singkat"></textarea>
                        @error('description')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-3 text-sm font-medium text-slate-700">
                        <input wire:model="isActive" type="checkbox" class="rounded border-slate-300 text-hotel-700 focus:ring-hotel-500">
                        Aktif dan dapat dipilih
                    </label>
                    <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $roomTypeId ? 'Simpan perubahan' : 'Buat tipe kamar' }}</span>
                        <span wire:loading wire:target="save">Menyimpan…</span>
                    </button>
                </div>
            </form>
        @endcanany

        <section class="panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr><th class="px-5 py-3">Tipe</th><th class="px-5 py-3">Tarif</th><th class="px-5 py-3">Kapasitas</th><th class="px-5 py-3">Kamar</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($roomTypes as $roomType)
                            <tr wire:key="room-type-{{ $roomType->id }}" class="hover:bg-slate-50/70">
                                <td class="px-5 py-4"><p class="font-bold text-slate-900">{{ $roomType->name }}</p><p class="text-xs text-slate-500">{{ $roomType->code }}</p></td>
                                <td class="whitespace-nowrap px-5 py-4 font-medium">Rp{{ number_format((float) $roomType->base_rate, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $roomType->capacity_adult }} dewasa · {{ $roomType->capacity_child }} anak</td>
                                <td class="px-5 py-4">{{ $roomType->rooms_count }}</td>
                                <td class="px-5 py-4"><span class="badge {{ $roomType->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $roomType->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    @can('update', $roomType)<button wire:click="edit({{ $roomType->id }})" class="px-2 py-1 font-semibold text-hotel-700 hover:text-hotel-900">Edit</button>@endcan
                                    @can('delete', $roomType)<button wire:click="delete({{ $roomType->id }})" data-confirm="Tipe kamar {{ $roomType->name }} akan dihapus. Tindakan ini hanya dapat dilakukan jika belum digunakan oleh kamar." data-confirm-title="Hapus tipe kamar?" data-confirm-label="Ya, hapus tipe" data-confirm-tone="danger" class="px-2 py-1 font-semibold text-rose-600 hover:text-rose-800">Hapus</button>@endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">Belum ada tipe kamar yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($roomTypes->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $roomTypes->links() }}</div>@endif
        </section>
    </div>
</div>
