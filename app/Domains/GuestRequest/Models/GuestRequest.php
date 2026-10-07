<?php

namespace App\Domains\GuestRequest\Models;

use App\Domains\Guest\Models\Guest;
use App\Domains\GuestRequest\Enums\GuestRequestCategory;
use App\Domains\GuestRequest\Enums\GuestRequestDepartment;
use App\Domains\GuestRequest\Enums\GuestRequestPriority;
use App\Domains\GuestRequest\Enums\GuestRequestStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestRequest extends Model
{
    protected $fillable = [
        'request_number', 'guest_id', 'reservation_id', 'room_id', 'category',
        'description', 'priority', 'assigned_department', 'assigned_to', 'status',
        'requested_at', 'assigned_at', 'started_at', 'completed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'category' => GuestRequestCategory::class,
            'priority' => GuestRequestPriority::class,
            'assigned_department' => GuestRequestDepartment::class,
            'status' => GuestRequestStatus::class,
            'requested_at' => 'datetime',
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function responseMinutes(): ?int
    {
        $respondedAt = $this->started_at ?? $this->completed_at;

        return $respondedAt ? (int) $this->requested_at->diffInMinutes($respondedAt) : null;
    }
}
