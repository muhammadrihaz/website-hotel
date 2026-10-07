<div>
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-hotel-700">Operations</p><h2 class="mt-1 text-2xl font-black text-slate-950">Maintenance</h2><p class="mt-1 text-sm text-slate-500">Kelola work order dan blokir kamar bermasalah secara aman.</p></div>
        <div class="grid gap-2 sm:grid-cols-3"><input wire:model.live.debounce.250ms="search" class="form-input" placeholder="Ticket, room, masalah..."><select wire:model.live="statusFilter" class="form-input"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select><select wire:model.live="priorityFilter" class="form-input"><option value="">Semua prioritas</option>@foreach($priorities as $priorityOption)<option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>@endforeach</select></div>
    </div>
    <x-alert-notification />
    @error('maintenance')<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>@enderror

    @can('maintenance.create')
        <section class="panel mb-5 p-5">
            <div class="mb-4"><h3 class="font-bold text-slate-900">Buat maintenance ticket</h3><p class="text-xs text-slate-500">Priority Critical otomatis membuat kamar OUT OF ORDER.</p></div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div><label class="form-label">Kamar</label><select wire:model="roomId" class="form-input"><option value="">Area umum / tanpa kamar</option>@foreach($rooms as $room)<option value="{{ $room->id }}">Room {{ $room->room_number }} · Lantai {{ $room->floor }}</option>@endforeach</select></div>
                <div><label class="form-label">Lokasi alternatif</label><input wire:model="location" class="form-input" placeholder="Lobby, koridor..."></div>
                <div><label class="form-label">Kategori</label><select wire:model="category" class="form-input">@foreach($categories as $categoryOption)<option value="{{ $categoryOption->value }}">{{ $categoryOption->label() }}</option>@endforeach</select></div>
                <div><label class="form-label">Prioritas</label><select wire:model="priority" class="form-input">@foreach($priorities as $priorityOption)<option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>@endforeach</select></div>
                <div class="md:col-span-2 xl:col-span-3"><label class="form-label">Deskripsi masalah</label><textarea wire:model="description" class="form-input min-h-24" placeholder="Jelaskan gejala dan kondisi saat ditemukan..."></textarea>@error('description')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Foto sebelum</label><input wire:model="beforePhoto" type="file" accept="image/jpeg,image/png,image/webp" class="form-input">@error('beforePhoto')<p class="form-error">{{ $message }}</p>@enderror<label class="mt-3 flex items-center gap-2 text-xs font-semibold text-slate-700"><input wire:model="blocksRoom" type="checkbox" class="rounded border-slate-300 text-hotel-700"> Blokir kamar</label></div>
            </div>
            <button wire:click="save" wire:loading.attr="disabled" class="btn-primary mt-4">Buat ticket</button>
        </section>
    @endcan

    <div class="space-y-4">
        @forelse($tickets as $ticket)
            @php($statusTone = match($ticket->status->value) { 'OPEN' => 'bg-amber-100 text-amber-700', 'ASSIGNED' => 'bg-sky-100 text-sky-700', 'IN_PROGRESS' => 'bg-indigo-100 text-indigo-700', 'WAITING' => 'bg-orange-100 text-orange-700', 'RESOLVED' => 'bg-violet-100 text-violet-700', default => 'bg-emerald-100 text-emerald-700' })
            <article wire:key="maintenance-{{ $ticket->id }}" class="panel p-5">
                <div class="grid gap-5 xl:grid-cols-[1.2fr_.8fr_1fr]">
                    <div><div class="flex flex-wrap items-center gap-2"><span class="badge {{ $statusTone }}">{{ $ticket->status->label() }}</span><span class="text-xs font-black {{ in_array($ticket->priority->value, ['HIGH','CRITICAL'], true) ? 'text-rose-700' : 'text-slate-500' }}">{{ $ticket->priority->label() }}</span>@if($ticket->blocks_room)<span class="badge bg-rose-600 text-white">ROOM BLOCKED</span>@endif</div><h3 class="mt-3 text-lg font-black text-slate-950">{{ $ticket->ticket_number }} · {{ $ticket->category->label() }}</h3><p class="mt-1 text-sm text-slate-600">{{ $ticket->description }}</p><p class="mt-2 text-xs text-slate-500">{{ $ticket->room ? 'Room '.$ticket->room->room_number : $ticket->location }} · Dilaporkan {{ $ticket->created_at->diffForHumans() }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4 text-sm"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Penanganan</p><p class="mt-2 font-bold text-slate-800">{{ $ticket->assignee?->name ?? 'Belum ditugaskan' }}</p><p class="mt-1 text-xs text-slate-500">{{ $ticket->started_at ? 'Mulai '.$ticket->started_at->format('d M H:i') : 'Belum mulai' }}</p>@if($ticket->resolution)<p class="mt-3 border-t border-slate-200 pt-3 text-xs text-slate-600"><strong>Resolusi:</strong> {{ $ticket->resolution }}</p>@endif</div>
                    <div class="space-y-2">
                        @if(in_array($ticket->status->value, ['OPEN','ASSIGNED'], true))
                            @can('maintenance.assign')<div class="flex gap-2"><select wire:model="assignees.{{ $ticket->id }}" class="form-input"><option value="">Pilih teknisi</option>@foreach($staff as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select><button wire:click="assign({{ $ticket->id }})" class="btn-secondary">Assign</button></div>@endcan
                            @can('maintenance.update')<button wire:click="start({{ $ticket->id }})" class="btn-primary w-full">Mulai pengerjaan</button>@endcan
                        @elseif(in_array($ticket->status->value, ['IN_PROGRESS','WAITING','ASSIGNED'], true))
                            @can('maintenance.update')<textarea wire:model="resolutions.{{ $ticket->id }}" class="form-input" placeholder="Tindakan perbaikan..."></textarea><input wire:model="afterPhotos.{{ $ticket->id }}" type="file" accept="image/jpeg,image/png,image/webp" class="form-input"><div class="flex gap-2">@if($ticket->status->value === 'IN_PROGRESS')<button wire:click="waitForPart({{ $ticket->id }})" class="btn-secondary flex-1">Waiting</button>@endif<button wire:click="resolve({{ $ticket->id }})" class="btn-primary flex-1">Resolve</button></div>@endcan
                        @elseif($ticket->status->value === 'RESOLVED')
                            @can('maintenance.verify')<button wire:click="verify({{ $ticket->id }})" data-confirm="Pastikan perbaikan sudah diuji. Blokir kamar akan dilepas apabila tidak ada ticket blocking lainnya." data-confirm-title="Verifikasi perbaikan?" data-confirm-label="Ya, verifikasi" data-confirm-tone="primary" class="btn-primary w-full">Verifikasi perbaikan</button>@endcan
                        @elseif($ticket->status->value === 'VERIFIED')
                            @can('maintenance.verify')<button wire:click="close({{ $ticket->id }})" class="btn-secondary w-full">Tutup ticket</button>@endcan
                        @else
                            <p class="rounded-xl bg-emerald-50 p-3 text-center text-xs font-bold text-emerald-700">Ticket selesai</p>
                        @endif
                    </div>
                </div>
            </article>
        @empty<div class="panel px-6 py-16 text-center text-sm text-slate-500">Belum ada maintenance ticket.</div>@endforelse
    </div>
    @if($tickets->hasPages())<div class="mt-5">{{ $tickets->links() }}</div>@endif
</div>
