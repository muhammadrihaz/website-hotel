<?php

namespace App\Domains\Guest\Models;

use App\Domains\Guest\Enums\GuestGender;
use App\Domains\Guest\Enums\IdentityType;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Reservation\Models\Stay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'guest_code',
        'full_name',
        'gender',
        'phone',
        'phone_normalized',
        'email',
        'email_normalized',
        'identity_type',
        'identity_number',
        'identity_number_normalized',
        'nationality',
        'address',
        'date_of_birth',
        'notes',
        'is_blacklisted',
    ];

    protected function casts(): array
    {
        return [
            'gender' => GuestGender::class,
            'identity_type' => IdentityType::class,
            'date_of_birth' => 'date',
            'is_blacklisted' => 'boolean',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function stays(): HasMany
    {
        return $this->hasMany(Stay::class);
    }
}
