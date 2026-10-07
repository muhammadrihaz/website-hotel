<?php

namespace App\Domains\Guest\Livewire;

use App\Domains\Guest\Actions\GuestDuplicateDetector;
use App\Domains\Guest\Actions\SaveGuest;
use App\Domains\Guest\Enums\GuestGender;
use App\Domains\Guest\Enums\IdentityType;
use App\Domains\Guest\Models\Guest;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GuestsManager extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public ?int $guestId = null;

    public ?int $selectedGuestId = null;

    public string $fullName = '';

    public string $gender = '';

    public string $phone = '';

    public string $email = '';

    public string $identityType = '';

    public string $identityNumber = '';

    public string $nationality = 'Indonesia';

    public string $address = '';

    public string $dateOfBirth = '';

    public string $notes = '';

    public bool $isBlacklisted = false;

    public bool $duplicateConfirmed = false;

    /** @var list<array{id: int, guest_code: string, full_name: string}> */
    public array $duplicateMatches = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPhone(): void
    {
        $this->resetDuplicateConfirmation();
    }

    public function updatedEmail(): void
    {
        $this->resetDuplicateConfirmation();
    }

    public function updatedIdentityNumber(): void
    {
        $this->resetDuplicateConfirmation();
    }

    public function edit(int $id): void
    {
        $guest = Guest::query()->findOrFail($id);
        $this->authorize('update', $guest);

        $this->guestId = $guest->id;
        $this->fullName = $guest->full_name;
        $this->gender = $guest->gender?->value ?? '';
        $this->phone = $guest->phone ?? '';
        $this->email = $guest->email ?? '';
        $this->identityType = $guest->identity_type?->value ?? '';
        $this->identityNumber = $guest->identity_number ?? '';
        $this->nationality = $guest->nationality ?? '';
        $this->address = $guest->address ?? '';
        $this->dateOfBirth = $guest->date_of_birth?->format('Y-m-d') ?? '';
        $this->notes = $guest->notes ?? '';
        $this->isBlacklisted = $guest->is_blacklisted;
        $this->resetDuplicateConfirmation();
        $this->resetValidation();
    }

    public function showProfile(int $id): void
    {
        $guest = Guest::query()->findOrFail($id);
        $this->authorize('view', $guest);
        $this->selectedGuestId = $guest->id;
    }

    public function closeProfile(): void
    {
        $this->selectedGuestId = null;
    }

    public function save(SaveGuest $action, GuestDuplicateDetector $detector): void
    {
        $guest = $this->guestId ? Guest::query()->findOrFail($this->guestId) : null;
        $this->authorize($guest ? 'update' : 'create', $guest ?? Guest::class);

        $validated = $this->validate([
            'fullName' => ['required', 'string', 'max:160'],
            'gender' => ['nullable', Rule::enum(GuestGender::class)],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'identityType' => ['nullable', Rule::enum(IdentityType::class)],
            'identityNumber' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:2000'],
            'dateOfBirth' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'isBlacklisted' => ['boolean'],
            'duplicateConfirmed' => ['boolean'],
        ]);

        $data = [
            'full_name' => trim($validated['fullName']),
            'gender' => $validated['gender'] ?: null,
            'phone' => filled($validated['phone']) ? trim($validated['phone']) : null,
            'email' => filled($validated['email']) ? mb_strtolower(trim($validated['email'])) : null,
            'identity_type' => $validated['identityType'] ?: null,
            'identity_number' => filled($validated['identityNumber']) ? trim($validated['identityNumber']) : null,
            'nationality' => filled($validated['nationality']) ? trim($validated['nationality']) : null,
            'address' => filled($validated['address']) ? trim($validated['address']) : null,
            'date_of_birth' => $validated['dateOfBirth'] ?: null,
            'notes' => filled($validated['notes']) ? trim($validated['notes']) : null,
            'is_blacklisted' => $validated['isBlacklisted'],
        ];

        $duplicates = $detector->detect($data, $guest);
        if ($duplicates->isNotEmpty() && ! $this->duplicateConfirmed) {
            $this->duplicateMatches = $duplicates->map(fn (Guest $match): array => [
                'id' => $match->id,
                'guest_code' => $match->guest_code,
                'full_name' => $match->full_name,
            ])->all();
            $this->addError('duplicate', 'Guest dengan telepon, email, atau nomor identitas serupa ditemukan. Tinjau dan konfirmasi untuk melanjutkan.');

            return;
        }

        $action->execute($data, $guest, $this->duplicateConfirmed);
        session()->flash('status', $guest ? 'Data guest berhasil diperbarui.' : 'Guest baru berhasil dibuat.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset([
            'guestId', 'fullName', 'gender', 'phone', 'email', 'identityType',
            'identityNumber', 'address', 'dateOfBirth', 'notes', 'isBlacklisted',
        ]);
        $this->nationality = 'Indonesia';
        $this->resetDuplicateConfirmation();
        $this->resetValidation();
    }

    public function render(): View
    {
        $guests = Guest::query()
            ->withCount(['reservations', 'stays'])
            ->when($this->search, fn ($query) => $query->where(function ($query): void {
                $query->where('guest_code', 'like', "%{$this->search}%")
                    ->orWhere('full_name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('identity_number', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(12);

        $profile = $this->selectedGuestId
            ? Guest::query()->with([
                'reservations' => fn ($query) => $query->with(['room:id,room_number', 'roomType:id,name'])->latest()->limit(20),
                'stays' => fn ($query) => $query->with('room:id,room_number')->latest('checked_in_at')->limit(20),
            ])->findOrFail($this->selectedGuestId)
            : null;

        return view('livewire.guests.guests-manager', [
            'guests' => $guests,
            'profile' => $profile,
            'genders' => GuestGender::cases(),
            'identityTypes' => IdentityType::cases(),
        ]);
    }

    private function resetDuplicateConfirmation(): void
    {
        $this->duplicateConfirmed = false;
        $this->duplicateMatches = [];
        $this->resetErrorBag('duplicate');
    }
}
