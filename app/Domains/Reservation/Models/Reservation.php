<?php

namespace App\Domains\Reservation\Models;

use App\Domains\Guest\Models\Guest;
use App\Domains\Reservation\Enums\BookingSource;
use App\Domains\Reservation\Enums\PaymentStatus;
use App\Domains\Reservation\Enums\ReservationStatus;
use App\Domains\Room\Models\Room;
use App\Domains\Room\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    protected $fillable = [
        'reservation_number',
        'guest_id',
        'room_type_id',
        'room_id',
        'booking_source',
        'booking_reference',
        'check_in_date',
        'check_out_date',
        'adult_count',
        'child_count',
        'room_rate',
        'total_room_amount',
        'additional_charge',
        'discount',
        'paid_amount',
        'security_deposit_amount',
        'total_amount',
        'payment_status',
        'reservation_status',
        'special_request',
        'internal_note',
        'created_by',
        'checked_in_at',
        'checked_in_by',
        'checked_out_at',
        'checked_out_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'booking_source' => BookingSource::class,
            'payment_status' => PaymentStatus::class,
            'reservation_status' => ReservationStatus::class,
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'room_rate' => 'decimal:2',
            'total_room_amount' => 'decimal:2',
            'additional_charge' => 'decimal:2',
            'discount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'security_deposit_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(['name' => 'System']);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by')->withDefault(['name' => 'System']);
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by')->withDefault(['name' => 'System']);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ReservationStatusHistory::class);
    }

    public function stay(): HasOne
    {
        return $this->hasOne(Stay::class);
    }

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('reservation_status', array_map(
            fn (ReservationStatus $status): string => $status->value,
            ReservationStatus::blocking(),
        ));
    }

    public function scopeOverlapping(Builder $query, string $checkIn, string $checkOut): Builder
    {
        return $query
            ->whereDate('check_in_date', '<', $checkOut)
            ->whereDate('check_out_date', '>', $checkIn);
    }

    public function nightCount(): int
    {
        return $this->check_in_date->diffInDays($this->check_out_date);
    }
}
