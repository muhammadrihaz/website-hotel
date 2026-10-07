<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-hotel-700">Staff Operations</p>
            <h2 class="mt-1 text-2xl font-black text-slate-950">Shift Handover</h2>
            <p class="mt-1 text-sm text-slate-500">Pastikan pekerjaan unfinished terbaca dan diterima shift berikutnya.</p>
        </div>
        <div class="grid gap-2 sm:grid-cols-2">
            <input wire:model.live.debounce.250ms="search" class="form-input" placeholder="Cari catatan / item...">
            <select wire:model.live="statusFilter" class="form-input">
                <option value="">Semua status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <x-alert-notification />

    @error('handover')
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>
    @enderror

    <div class="grid gap-5 xl:grid-cols-[.8fr_1.2fr]">
        <div class="space-y-5">
            @can('shift_handover.create')
                <section class="panel p-5">
                    <h3 class="font-bold text-slate-900">Buat draft shift</h3>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Tanggal</label>
                            <input wire:model="shiftDate" type="date" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Shift</label>
                            <select wire:model="shiftType" class="form-input">
                                @foreach($shiftTypes as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Penerima awal</label>
                            <select wire:model="handoverTo" class="form-input">
                                <option value="">Tentukan saat dikirim</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Catatan shift</label>
                            <textarea wire:model="notes" class="form-input" placeholder="Ringkasan kondisi shift..."></textarea>
                        </div>
                    </div>
                    <button wire:click="createHandover" class="btn-primary mt-4 w-full">Buat draft</button>
                </section>
            @endcan

            @if($activeHandover && $activeHandover->status->value === 'DRAFT')
                @can('shift_handover.update')
                    <section class="panel p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-slate-900">Tambah item</h3>
                            <span class="badge bg-slate-100 text-slate-600">Draft #{{ $activeHandover->id }}</span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Kategori</label>
                                <select wire:model="itemCategory" class="form-input">
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}">{{ str($category)->replace('_', ' ')->title() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Prioritas</label>
                                <select wire:model="itemPriority" class="form-input">
                                    @foreach($priorities as $priorityOption)
                                        <option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Room</label>
                                <select wire:model="itemRoomId" class="form-input">
                                    <option value="">Tidak terkait</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}">Room {{ $room->room_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Reservasi</label>
                                <select wire:model="itemReservationId" class="form-input">
                                    <option value="">Tidak terkait</option>
                                    @foreach($reservations as $reservation)
                                        <option value="{{ $reservation->id }}">{{ $reservation->reservation_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="form-label">Tindak lanjut</label>
                                <textarea wire:model="itemDescription" class="form-input" placeholder="Apa yang harus diketahui / dilakukan shift berikutnya?"></textarea>
                                @error('itemDescription')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <button wire:click="addItem" class="btn-primary mt-4 w-full">Tambah item handover</button>
                    </section>
                @endcan
            @endif
        </div>

        <div class="space-y-4">
            @forelse($handovers as $handover)
                @php
                    $isSelected = $activeHandoverId === $handover->id;
                    $statusTone = match($handover->status->value) {
                        'DRAFT' => 'bg-slate-100 text-slate-700 ring-slate-200',
                        'HANDED_OVER' => 'bg-amber-50 text-amber-700 ring-amber-200',
                        'ACKNOWLEDGED' => 'bg-sky-50 text-sky-700 ring-sky-200',
                        default => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    };
                    $shiftTone = match($handover->shift_type->value) {
                        'MORNING' => 'bg-amber-50 text-amber-600 ring-amber-100',
                        'AFTERNOON' => 'bg-orange-50 text-orange-600 ring-orange-100',
                        default => 'bg-indigo-50 text-indigo-600 ring-indigo-100',
                    };
                @endphp

                <article wire:key="handover-{{ $handover->id }}"
                    @class([
                        'panel relative overflow-hidden transition duration-200',
                        'border-hotel-300 shadow-md shadow-hotel-100/70 ring-2 ring-hotel-100' => $isSelected,
                        'hover:border-slate-300 hover:shadow-md' => ! $isSelected,
                    ])>
                    @if($isSelected)
                        <span class="absolute inset-y-0 left-0 w-1 bg-hotel-500" aria-hidden="true"></span>
                    @endif

                    <button wire:click="selectHandover({{ $handover->id }})" type="button" class="group w-full px-5 pb-4 pt-5 text-left sm:px-6 sm:pt-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3.5">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-1 {{ $shiftTone }}">
                                    @if($handover->shift_type->value === 'NIGHT')
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.5 15.2A8.5 8.5 0 118.8 3.5a7 7 0 0011.7 11.7z" /></svg>
                                    @else
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="3.5" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" /></svg>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ $handover->shift_date->translatedFormat('l, d F Y') }}</p>
                                    <h3 class="mt-1 text-lg font-black text-slate-950">{{ $handover->shift_type->label() }} Shift</h3>
                                    <p class="mt-1 text-xs font-medium text-slate-500">Handover #{{ str_pad((string) $handover->id, 4, '0', STR_PAD_LEFT) }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 sm:max-w-[250px] sm:justify-end">
                                <span class="badge ring-1 {{ $statusTone }}">{{ $handover->status->label() }}</span>
                                @if($handover->open_items_count)
                                    <span class="badge bg-rose-50 text-rose-700 ring-1 ring-rose-200">{{ $handover->open_items_count }} unfinished</span>
                                @else
                                    <span class="badge bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">Semua selesai</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)_auto] sm:items-center">
                            <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Dibuat oleh</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-800">{{ $handover->creator->name }}</p>
                            </div>
                            <svg class="hidden h-4 w-4 text-slate-300 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-4-4l4 4-4 4" /></svg>
                            <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Diteruskan kepada</p>
                                <p class="mt-1 truncate text-sm font-bold {{ $handover->recipient ? 'text-slate-800' : 'text-amber-700' }}">{{ $handover->recipient?->name ?? 'Belum ada penerima' }}</p>
                            </div>
                            <div class="flex items-center gap-2 rounded-xl bg-slate-950 px-3.5 py-3 text-white">
                                <svg class="h-4 w-4 text-hotel-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" /></svg>
                                <span class="whitespace-nowrap text-sm font-black">{{ $handover->items_count }} item</span>
                            </div>
                        </div>

                        @if(filled($handover->notes))
                            <div class="mt-3 flex items-start gap-2.5 rounded-xl border border-sky-100 bg-sky-50/70 px-3.5 py-3 text-sm leading-5 text-sky-900">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 8h10M7 12h7m-9 9l2.5-4H19a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2v4z" /></svg>
                                <span>{{ $handover->notes }}</span>
                            </div>
                        @endif

                        <div class="mt-3 flex items-center justify-end gap-1.5 text-xs font-bold text-hotel-700 opacity-80 transition group-hover:opacity-100">
                            <span>{{ $isSelected ? 'Handover dipilih' : 'Pilih handover' }}</span>
                            <svg class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </button>

                    <div class="border-t border-slate-100 bg-slate-50/50 px-5 py-5 sm:px-6">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-black text-slate-900">Item tindak lanjut</h4>
                                <p class="mt-0.5 text-xs text-slate-500">Pekerjaan yang perlu diperhatikan shift berikutnya</p>
                            </div>
                            <span class="text-xs font-bold text-slate-400">{{ $handover->items_count }} total</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($handover->items as $item)
                                @php
                                    $isCompleted = $item->status->value === 'COMPLETED';
                                    $priorityTone = match($item->priority->value) {
                                        'URGENT' => 'bg-rose-500',
                                        'HIGH' => 'bg-orange-500',
                                        'LOW' => 'bg-sky-400',
                                        default => 'bg-amber-400',
                                    };
                                    $priorityBadge = match($item->priority->value) {
                                        'URGENT' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                        'HIGH' => 'bg-orange-50 text-orange-700 ring-orange-200',
                                        'LOW' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                        default => 'bg-amber-50 text-amber-700 ring-amber-200',
                                    };
                                @endphp

                                <div @class([
                                    'relative overflow-hidden rounded-2xl border bg-white p-4 shadow-sm',
                                    'border-emerald-200 bg-emerald-50/30' => $isCompleted,
                                    'border-slate-200' => ! $isCompleted,
                                ])>
                                    <span class="absolute inset-y-0 left-0 w-1 {{ $isCompleted ? 'bg-emerald-500' : $priorityTone }}" aria-hidden="true"></span>
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0 flex-1 pl-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="badge bg-slate-100 text-slate-600">{{ str($item->category)->replace('_', ' ')->title() }}</span>
                                                <span class="badge ring-1 {{ $priorityBadge }}">{{ $item->priority->label() }}</span>
                                                @if($item->room)
                                                    <span class="badge bg-violet-50 text-violet-700">Room {{ $item->room->room_number }}</span>
                                                @endif
                                                @if($item->reservation)
                                                    <span class="badge bg-sky-50 text-sky-700">{{ $item->reservation->reservation_number }}</span>
                                                @endif
                                            </div>
                                            <p @class([
                                                'mt-3 text-sm font-bold leading-6 text-slate-800',
                                                'line-through opacity-60' => $isCompleted,
                                            ])>{{ $item->description }}</p>
                                        </div>

                                        @can('shift_handover.update')
                                            <div class="w-full shrink-0 sm:w-44">
                                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Status item</label>
                                                <select wire:change="setItemStatus({{ $item->id }}, $event.target.value)" class="form-input">
                                                    <option value="OPEN" @selected($item->status->value === 'OPEN')>Open</option>
                                                    <option value="IN_PROGRESS" @selected($item->status->value === 'IN_PROGRESS')>In Progress</option>
                                                    <option value="COMPLETED" @selected($item->status->value === 'COMPLETED')>Completed</option>
                                                </select>
                                            </div>
                                        @else
                                            <span class="badge {{ $isCompleted ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $item->status->label() }}</span>
                                        @endcan
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-9 text-center">
                                    <svg class="mx-auto h-7 w-7 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 6h13M8 12h13M8 18h8M3 6h.01M3 12h.01M3 18h.01" /></svg>
                                    <p class="mt-2 text-sm font-semibold text-slate-500">Belum ada item handover.</p>
                                </div>
                            @endforelse
                        </div>

                        @can('shift_handover.update')
                            <div class="mt-4 border-t border-slate-200 pt-4">
                                @if($handover->status->value === 'DRAFT')
                                    <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]">
                                        <select wire:model="recipients.{{ $handover->id }}" class="form-input">
                                            <option value="">Pilih penerima shift</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        <button wire:click="sendHandover({{ $handover->id }})" class="btn-primary whitespace-nowrap">Kirim handover</button>
                                    </div>
                                @elseif($handover->status->value === 'HANDED_OVER')
                                    <button wire:click="acknowledge({{ $handover->id }})" class="btn-primary w-full">Terima handover</button>
                                @endif
                            </div>
                        @endcan
                    </div>
                </article>
            @empty
                <div class="panel px-6 py-16 text-center text-sm text-slate-500">Belum ada shift handover.</div>
            @endforelse

            @if($handovers->hasPages())
                <div>{{ $handovers->links() }}</div>
            @endif
        </div>
    </div>
</div>
