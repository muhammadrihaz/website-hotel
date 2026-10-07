<?php

namespace App\Domains\Housekeeping\Models;

use App\Domains\Housekeeping\Enums\HousekeepingPriority;
use App\Domains\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Room\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HousekeepingTask extends Model
{
    protected $fillable = [
        'room_id',
        'reservation_id',
        'assigned_to',
        'priority',
        'status',
        'started_at',
        'completed_at',
        'verified_at',
        'verified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'priority' => HousekeepingPriority::class,
            'status' => HousekeepingTaskStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(HousekeepingChecklistItem::class)->orderBy('id');
    }

    public function checklistProgress(): int
    {
        $total = $this->checklistItems->count();

        return $total > 0 ? (int) round(($this->checklistItems->where('is_completed', true)->count() / $total) * 100) : 0;
    }
}
