<?php

namespace App\Domains\LostFound\Models;

use App\Domains\Guest\Models\Guest;
use App\Domains\LostFound\Enums\LostFoundStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LostFoundItem extends Model
{
    protected $fillable = [
        'code', 'item_name', 'description', 'found_location', 'found_date', 'found_by',
        'guest_id', 'reservation_id', 'photo', 'storage_location', 'status', 'notes', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'found_date' => 'date',
            'status' => LostFoundStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function finder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'found_by')->withDefault(['name' => 'System']);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
