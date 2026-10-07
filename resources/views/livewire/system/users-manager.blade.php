<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h2 class="text-2xl font-black text-slate-950">Pengguna</h2><p class="mt-1 text-sm text-slate-500">Akses diberikan melalui permission pada role, bukan nama role di kode.</p></div>
        <input wire:model.live.debounce.300ms="search" class="form-input w-full sm:w-72" placeholder="Cari nama atau email…">
    </div>
    <x-alert-notification />

    <div class="grid items-start gap-6 xl:grid-cols-[380px_1fr]">
        @canany(['user.create', 'user.update'])
            <form wire:submit="save" class="panel p-5">
                <div class="mb-5 flex items-center justify-between"><h3 class="font-bold text-slate-900">{{ $userId ? 'Edit pengguna' : 'Pengguna baru' }}</h3>@if($userId)<button type="button" wire:click="resetForm" class="text-sm font-semibold text-slate-500">Batal</button>@endif</div>
                <div class="space-y-4">
                    <div><label class="form-label">Nama lengkap</label><input wire:model="name" class="form-input" autocomplete="off">@error('name')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div><label class="form-label">Email</label><input wire:model="email" type="email" class="form-input" autocomplete="off">@error('email')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div><label class="form-label">Kata sandi {{ $userId ? '(opsional)' : '' }}</label><input wire:model="password" type="password" class="form-input" autocomplete="new-password" placeholder="Minimal 10 karakter">@error('password')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div><label class="form-label">Konfirmasi kata sandi</label><input wire:model="passwordConfirmation" type="password" class="form-input" autocomplete="new-password">@error('passwordConfirmation')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <fieldset>
                        <legend class="form-label">Role</legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($availableRoles as $role)
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700"><input wire:model="roles" type="checkbox" value="{{ $role->name }}" class="rounded border-slate-300 text-hotel-700 focus:ring-hotel-500">{{ $role->name }}</label>
                            @endforeach
                        </div>
                        @error('roles')<p class="form-error">{{ $message }}</p>@enderror
                        @error('roles.*')<p class="form-error">{{ $message }}</p>@enderror
                    </fieldset>
                    <label class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-3 text-sm font-medium text-slate-700"><input wire:model="isActive" type="checkbox" class="rounded border-slate-300 text-hotel-700 focus:ring-hotel-500">Akun aktif</label>
                    @error('isActive')<p class="form-error">{{ $message }}</p>@enderror
                    <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $userId ? 'Simpan perubahan' : 'Buat pengguna' }}</span><span wire:loading wire:target="save">Menyimpan…</span></button>
                </div>
            </form>
        @endcanany

        <section class="panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Pengguna</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Login terakhir</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                            <tr wire:key="user-{{ $user->id }}" class="hover:bg-slate-50/70">
                                <td class="px-5 py-4"><p class="font-bold text-slate-900">{{ $user->name }}</p><p class="text-xs text-slate-500">{{ $user->email }}</p></td>
                                <td class="px-5 py-4"><div class="flex flex-wrap gap-1">@foreach($user->roles as $role)<span class="badge bg-slate-100 text-slate-700">{{ $role->name }}</span>@endforeach</div></td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $user->last_login_at?->diffForHumans() ?? 'Belum pernah' }}</td>
                                <td class="px-5 py-4"><span class="badge {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    @can('update', $user)<button wire:click="edit({{ $user->id }})" class="px-2 py-1 font-semibold text-hotel-700">Edit</button>@endcan
                                    @if(auth()->id() !== $user->id && auth()->user()->can($user->is_active ? 'deactivate' : 'update', $user))<button wire:click="toggleActive({{ $user->id }})" data-confirm="{{ $user->is_active ? 'Pengguna tidak dapat login sampai akun diaktifkan kembali.' : 'Pengguna akan kembali dapat login dan menggunakan permission yang dimilikinya.' }}" data-confirm-title="{{ $user->is_active ? 'Nonaktifkan pengguna?' : 'Aktifkan pengguna?' }}" data-confirm-label="{{ $user->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan' }}" data-confirm-tone="{{ $user->is_active ? 'danger' : 'primary' }}" class="px-2 py-1 font-semibold {{ $user->is_active ? 'text-rose-600' : 'text-emerald-700' }}">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">Belum ada pengguna yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>@endif
        </section>
    </div>
</div>
