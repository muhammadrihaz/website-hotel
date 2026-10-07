<div>
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-hotel-700">Operations</p>
            <h2 class="mt-1 text-2xl font-black text-slate-950">Housekeeping Board</h2>
            <p class="mt-1 text-sm text-slate-500">Room hanya menjadi READY setelah checklist wajib selesai dan diverifikasi.</p>
        </div>
        <div class="grid gap-2 sm:grid-cols-3">
            <input wire:model.live.debounce.250ms="search" class="form-input" placeholder="Cari room / reservasi...">
            <select wire:model.live="statusFilter" class="form-input"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select>
            <select wire:model.live="floorFilter" class="form-input"><option value="">Semua lantai</option>@foreach($floors as $floor)<option value="{{ $floor }}">Lantai {{ $floor }}</option>@endforeach</select>
        </div>
    </div>

    <x-alert-notification />
    @error('housekeeping')<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>@enderror
    @error('checklist')<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>@enderror

    @can('housekeeping.update')
        <section class="panel mb-5 p-5">
            <div class="grid gap-3 lg:grid-cols-[1fr_.55fr_1.3fr_auto] lg:items-end">
                <div><label class="form-label">Kamar DIRTY tanpa task</label><select wire:model="taskRoomId" class="form-input"><option value="">Pilih kamar</option>@foreach($dirtyRooms as $room)<option value="{{ $room->id }}">Room {{ $room->room_number }} · Lantai {{ $room->floor }}</option>@endforeach</select>@error('taskRoomId')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Prioritas</label><select wire:model="priority" class="form-input">@foreach($priorities as $priorityOption)<option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>@endforeach</select></div>
                <div><label class="form-label">Catatan</label><input wire:model="notes" class="form-input" placeholder="Instruksi khusus..."></div>
                <button wire:click="createTask" class="btn-primary whitespace-nowrap">Buat task</button>
            </div>
        </section>
    @endcan

    <div class="grid gap-4 xl:grid-cols-2 2xl:grid-cols-3">
        @forelse($tasks as $task)
            @php
                $statusTone = match($task->status->value) { 'PENDING' => 'bg-amber-100 text-amber-700', 'ASSIGNED' => 'bg-sky-100 text-sky-700', 'CLEANING' => 'bg-indigo-100 text-indigo-700', 'COMPLETED' => 'bg-violet-100 text-violet-700', default => 'bg-emerald-100 text-emerald-700' };
                $priorityTone = in_array($task->priority->value, ['URGENT', 'HIGH'], true) ? 'text-rose-700' : 'text-slate-500';
                $completed = $task->checklistItems->where('is_completed', true)->count();
                $totalItems = $task->checklistItems->count();
            @endphp
            <article wire:key="housekeeping-{{ $task->id }}" class="panel overflow-hidden">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div><p class="text-xs font-bold uppercase tracking-wider {{ $priorityTone }}">{{ $task->priority->label() }} priority</p><h3 class="mt-1 text-2xl font-black text-slate-950">Room {{ $task->room->room_number }}</h3><p class="text-xs text-slate-500">{{ $task->room->roomType->name }} · Lantai {{ $task->room->floor }}</p></div>
                        <span class="badge {{ $statusTone }}">{{ $task->status->label() }}</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3 text-xs"><div><span class="text-slate-500">Assignee</span><p class="mt-0.5 font-bold text-slate-800">{{ $task->assignee?->name ?? 'Belum ditentukan' }}</p></div><div><span class="text-slate-500">Checklist</span><p class="mt-0.5 font-bold text-slate-800">{{ $completed }}/{{ $totalItems }} selesai</p></div></div>
                    @if($task->notes)<p class="mt-3 text-sm text-slate-600">{{ $task->notes }}</p>@endif

                    @if(in_array($task->status->value, ['CLEANING', 'COMPLETED', 'VERIFIED'], true))
                        <details class="mt-4 rounded-xl border border-slate-200 p-3" @if($task->status->value === 'CLEANING') open @endif>
                            <summary class="cursor-pointer text-sm font-bold text-slate-800">Checklist kamar</summary>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach($task->checklistItems as $item)
                                    <button type="button" wire:click="toggleChecklist({{ $task->id }}, {{ $item->id }})" @disabled($task->status->value !== 'CLEANING') class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left text-xs {{ $item->is_completed ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-50 text-slate-600' }} disabled:cursor-default">
                                        <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border {{ $item->is_completed ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 bg-white' }}">@if($item->is_completed)✓@endif</span><span>{{ $item->label }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>

                <div class="border-t border-slate-100 bg-slate-50/60 p-4">
                    @if(in_array($task->status->value, ['PENDING', 'ASSIGNED'], true))
                        <div class="flex flex-col gap-2 sm:flex-row">
                            @can('housekeeping.assign')<select wire:model="assignees.{{ $task->id }}" class="form-input flex-1"><option value="">Pilih petugas</option>@foreach($staff as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select><button wire:click="assign({{ $task->id }})" class="btn-secondary">Assign</button>@endcan
                            @can('housekeeping.update')<button wire:click="start({{ $task->id }})" class="btn-primary">Mulai</button>@endcan
                        </div>
                    @elseif($task->status->value === 'CLEANING')
                        @can('housekeeping.update')<button wire:click="complete({{ $task->id }})" class="btn-primary w-full">Selesaikan cleaning</button>@endcan
                    @elseif($task->status->value === 'COMPLETED')
                        @can('housekeeping.verify')<button wire:click="verify({{ $task->id }})" data-confirm="Pastikan seluruh hasil cleaning sudah diperiksa. Kamar akan diubah menjadi READY dan dapat dijual kembali." data-confirm-title="Verifikasi housekeeping?" data-confirm-label="Ya, verifikasi" data-confirm-tone="primary" class="btn-primary w-full">Verifikasi & jadikan READY</button>@endcan
                    @else
                        <p class="text-center text-xs font-semibold text-emerald-700">Diverifikasi {{ $task->verified_at?->diffForHumans() }} oleh {{ $task->verifier?->name ?? 'System' }}</p>
                    @endif
                </div>
            </article>
        @empty
            <div class="panel col-span-full px-6 py-16 text-center"><p class="font-bold text-slate-800">Tidak ada housekeeping task.</p><p class="mt-1 text-sm text-slate-500">Task checkout dan task manual akan muncul di sini.</p></div>
        @endforelse
    </div>
    @if($tasks->hasPages())<div class="mt-5">{{ $tasks->links() }}</div>@endif
</div>
