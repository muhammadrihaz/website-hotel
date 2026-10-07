<div>
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-hotel-700">Guest Service</p><h2 class="mt-1 text-2xl font-black text-slate-950">Guest Requests</h2><p class="mt-1 text-sm text-slate-500">Pantau permintaan guest, PIC, dan waktu respons.</p></div>
        <div class="grid gap-2 sm:grid-cols-3"><input wire:model.live.debounce.250ms="search" class="form-input" placeholder="Request, guest, room..."><select wire:model.live="statusFilter" class="form-input"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select><select wire:model.live="departmentFilter" class="form-input"><option value="">Semua departemen</option>@foreach($departments as $department)<option value="{{ $department->value }}">{{ $department->label() }}</option>@endforeach</select></div>
    </div>
    <x-alert-notification />
    @error('guestRequest')<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</div>@enderror

    @can('guest_request.create')
        <section class="panel mb-5 p-5">
            <h3 class="font-bold text-slate-900">Catat permintaan baru</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="xl:col-span-2"><label class="form-label">Guest in-house</label><select wire:model="reservationId" class="form-input"><option value="">Pilih guest / room</option>@foreach($inHouseReservations as $reservation)<option value="{{ $reservation->id }}">Room {{ $reservation->room->room_number }} · {{ $reservation->guest->full_name }} · {{ $reservation->reservation_number }}</option>@endforeach</select>@error('reservationId')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Kategori</label><select wire:model="category" class="form-input">@foreach($categories as $categoryOption)<option value="{{ $categoryOption->value }}">{{ $categoryOption->label() }}</option>@endforeach</select></div>
                <div><label class="form-label">Prioritas</label><select wire:model="priority" class="form-input">@foreach($priorities as $priorityOption)<option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>@endforeach</select></div>
                <div><label class="form-label">Departemen</label><select wire:model="assignedDepartment" class="form-input">@foreach($departments as $department)<option value="{{ $department->value }}">{{ $department->label() }}</option>@endforeach</select></div>
                <div class="md:col-span-2"><label class="form-label">Permintaan / keluhan</label><input wire:model="description" class="form-input" placeholder="Contoh: extra towel 2 pcs...">@error('description')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label class="form-label">Catatan internal</label><input wire:model="notes" class="form-input" placeholder="Opsional"></div>
            </div>
            <button wire:click="save" class="btn-primary mt-4">Buat request</button>
        </section>
    @endcan

    <div class="grid gap-4 xl:grid-cols-2">
        @forelse($requests as $request)
            @php($statusTone = match($request->status->value) { 'OPEN' => 'bg-amber-100 text-amber-700', 'ASSIGNED' => 'bg-sky-100 text-sky-700', 'IN_PROGRESS' => 'bg-indigo-100 text-indigo-700', 'COMPLETED' => 'bg-emerald-100 text-emerald-700', default => 'bg-slate-100 text-slate-600' })
            <article wire:key="guest-request-{{ $request->id }}" class="panel p-5">
                <div class="flex items-start justify-between gap-3"><div><div class="flex flex-wrap items-center gap-2"><span class="badge {{ $statusTone }}">{{ $request->status->label() }}</span><span class="text-xs font-bold {{ in_array($request->priority->value, ['HIGH','URGENT'], true) ? 'text-rose-700' : 'text-slate-500' }}">{{ $request->priority->label() }}</span></div><h3 class="mt-3 font-black text-slate-950">Room {{ $request->room->room_number }} · {{ $request->category->label() }}</h3><p class="mt-1 text-sm text-slate-600">{{ $request->description }}</p></div><span class="text-xs font-bold text-slate-400">{{ $request->request_number }}</span></div>
                <div class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3 text-xs"><div><span class="text-slate-500">Guest</span><p class="mt-0.5 font-bold text-slate-800">{{ $request->guest->full_name }}</p></div><div><span class="text-slate-500">PIC · {{ $request->assigned_department->label() }}</span><p class="mt-0.5 font-bold text-slate-800">{{ $request->assignee?->name ?? 'Belum ditentukan' }}</p></div><div><span class="text-slate-500">Dibuat</span><p class="mt-0.5 font-bold text-slate-800">{{ $request->requested_at->diffForHumans() }}</p></div><div><span class="text-slate-500">Response time</span><p class="mt-0.5 font-bold text-slate-800">{{ $request->responseMinutes() !== null ? $request->responseMinutes().' menit' : 'Belum respons' }}</p></div></div>
                <div class="mt-4 space-y-2">
                    @if(in_array($request->status->value, ['OPEN','ASSIGNED'], true))
                        @can('guest_request.assign')<div class="flex gap-2"><select wire:model="assignees.{{ $request->id }}" class="form-input"><option value="">Pilih PIC</option>@foreach($staff as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select><button wire:click="assign({{ $request->id }})" class="btn-secondary">Assign</button></div>@endcan
                        @can('guest_request.update')<button wire:click="start({{ $request->id }})" class="btn-primary w-full">Mulai tangani</button>@endcan
                    @elseif($request->status->value === 'IN_PROGRESS')
                        @can('guest_request.update')<input wire:model="completionNotes.{{ $request->id }}" class="form-input" placeholder="Catatan penyelesaian..."><button wire:click="complete({{ $request->id }})" class="btn-primary w-full">Selesaikan</button>@endcan
                    @endif
                    @if(in_array($request->status->value, ['OPEN','ASSIGNED','IN_PROGRESS'], true))
                        @can('guest_request.update')<details><summary class="cursor-pointer text-xs font-bold text-rose-600">Batalkan request</summary><div class="mt-2 flex gap-2"><input wire:model="cancellationReasons.{{ $request->id }}" class="form-input" placeholder="Alasan pembatalan"><button wire:click="cancel({{ $request->id }})" class="btn-danger">Batalkan</button></div></details>@endcan
                    @endif
                </div>
            </article>
        @empty<div class="panel col-span-full px-6 py-16 text-center text-sm text-slate-500">Belum ada guest request.</div>@endforelse
    </div>
    @if($requests->hasPages())<div class="mt-5">{{ $requests->links() }}</div>@endif
</div>
