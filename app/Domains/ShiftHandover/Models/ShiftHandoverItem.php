<?php

namespace App\Domains\ShiftHandover\Models;

use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Models\Room;
use App\Domains\ShiftHandover\Enums\HandoverItemPriority;
use App\Domains\ShiftHandover\Enums\HandoverItemStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftHandoverItem extends Model
{
    protected $fillable = [
        'shift_handover_id', 'room_id', 'reservation_id', 'category', 'description',
        'priority', 'status', 'completed_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => HandoverItemPriority::class,
            'status' => HandoverItemStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(ShiftHandover::class, 'shift_handover_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
