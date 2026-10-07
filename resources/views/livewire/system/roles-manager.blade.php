<div>
    <div class="mb-6"><h2 class="text-2xl font-black text-slate-950">Roles & Permissions</h2><p class="mt-1 text-sm text-slate-500">Permission granular menjadi sumber kebenaran authorization backend dan menu.</p></div>
    <x-alert-notification />

    <div class="grid items-start gap-6 xl:grid-cols-[330px_1fr]">
        <section class="panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-bold text-slate-900">Daftar role</h3></div>
            <div class="divide-y divide-slate-100">
                @foreach($roles as $role)
                    <button wire:click="edit({{ $role->id }})" class="flex w-full items-center justify-between px-5 py-4 text-left transition hover:bg-slate-50">
                        <span><span class="block font-bold text-slate-900">{{ $role->name }}</span><span class="text-xs text-slate-500">{{ $role->users_count }} user</span></span>
                        <span class="badge bg-hotel-50 text-hotel-700">{{ $role->permissions_count }} permission</span>
                    </button>
                @endforeach
            </div>
            @can('role.create')<button wire:click="resetForm" class="m-4 btn-secondary w-[calc(100%-2rem)]">Role baru</button>@endcan
        </section>

        @canany(['role.create', 'role.update'])
            <form wire:submit="save" class="panel p-5 sm:p-6">
                <div class="mb-5 flex items-center justify-between"><div><h3 class="font-bold text-slate-900">{{ $roleId ? 'Atur role' : 'Role baru' }}</h3><p class="mt-1 text-xs text-slate-500">Centang permission yang benar-benar diperlukan.</p></div>@if($roleId)<button type="button" wire:click="resetForm" class="text-sm font-semibold text-slate-500">Batal</button>@endif</div>
                <div class="mb-6">
                    <label class="form-label">Nama role</label>
                    <input wire:model="name" class="form-input" @disabled($roleId && in_array($name, $protectedRoles, true))>
                    @if($roleId && in_array($name, $protectedRoles, true))<p class="mt-1 text-xs text-slate-500">Nama role sistem dilindungi, permission tetap dapat diubah.</p>@endif
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach($permissionGroups as $group => $permissions)
                        <fieldset class="rounded-xl border border-slate-200 p-4">
                            <div class="mb-3 flex items-center justify-between"><legend class="font-bold capitalize text-slate-800">{{ str($group)->replace('_', ' ') }}</legend><button type="button" wire:click="selectGroup('{{ $group }}')" class="text-xs font-semibold text-hotel-700">Pilih grup</button></div>
                            <div class="space-y-2">
                                @foreach($permissions as $permission)
                                    <label class="flex items-start gap-2 text-sm text-slate-600"><input wire:model="selectedPermissions" type="checkbox" value="{{ $permission->name }}" class="mt-0.5 rounded border-slate-300 text-hotel-700 focus:ring-hotel-500"><span>{{ $permission->name }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
                @error('selectedPermissions.*')<p class="form-error">{{ $message }}</p>@enderror
                <div class="mt-6 flex justify-end"><button class="btn-primary" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan role</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
            </form>
        @endcanany
    </div>
</div>
