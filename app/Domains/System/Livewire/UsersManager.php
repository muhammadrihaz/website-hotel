<?php

namespace App\Domains\System\Livewire;

use App\Domains\System\Actions\SaveUser;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsersManager extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    /** @var list<string> */
    public array $roles = [];

    public bool $isActive = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $user = User::query()->with('roles')->findOrFail($id);
        $this->authorize('update', $user);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->roles = $user->roles->pluck('name')->all();
        $this->isActive = $user->is_active;
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->resetValidation();
    }

    public function save(SaveUser $action): void
    {
        $user = $this->userId ? User::query()->findOrFail($this->userId) : null;
        $this->authorize($user ? 'update' : 'create', $user ?? User::class);

        if ($user && $user->is(auth()->user()) && ! $this->isActive) {
            throw ValidationException::withMessages([
                'isActive' => 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.',
            ]);
        }

        $currentRoles = $user?->getRoleNames()->sort()->values()->all() ?? [];
        $requestedRoles = collect($this->roles)->sort()->values()->all();
        if ($currentRoles !== $requestedRoles) {
            Gate::authorize('user.assign_role');
        }
        if (in_array('Owner', $requestedRoles, true)) {
            Gate::authorize('user.assign_owner');
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'email:rfc', 'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'password' => [Rule::requiredIf(! $this->userId), 'nullable', 'string', 'min:10', 'same:passwordConfirmation'],
            'passwordConfirmation' => [Rule::requiredIf(filled($this->password)), 'nullable', 'string'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'isActive' => ['boolean'],
        ]);

        $updatedUser = $action->execute([
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'password' => $validated['password'],
            'is_active' => $validated['isActive'],
        ], $validated['roles'], $user);

        session()->flash('status', $user ? 'Pengguna berhasil diperbarui.' : 'Pengguna berhasil dibuat.');
        $this->resetForm();
    }

    public function toggleActive(int $id, SaveUser $action): void
    {
        $user = User::query()->with('roles')->findOrFail($id);
        $this->authorize($user->is_active ? 'deactivate' : 'update', $user);

        $updatedUser = $action->execute([
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => ! $user->is_active,
        ], $user->getRoleNames()->all(), $user);

        session()->flash('status', $updatedUser->is_active ? 'Pengguna diaktifkan.' : 'Pengguna dinonaktifkan.');
    }

    public function resetForm(): void
    {
        $this->reset(['userId', 'name', 'email', 'password', 'passwordConfirmation', 'roles']);
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        $users = User::query()
            ->with('roles:id,name')
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.system.users-manager', [
            'users' => $users,
            'availableRoles' => Role::query()
                ->where('guard_name', 'web')
                ->when(! auth()->user()->can('user.assign_owner'), fn ($query) => $query->where('name', '!=', 'Owner'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
