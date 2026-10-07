<?php

namespace App\Domains\Reservation\Models;

use App\Domains\Reservation\Enums\ReservationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'reservation_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => ReservationStatus::class,
            'to_status' => ReservationStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by')->withDefault(['name' => 'System']);
    }
}
