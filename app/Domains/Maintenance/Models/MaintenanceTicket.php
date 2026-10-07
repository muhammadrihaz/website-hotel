<?php

namespace App\Domains\Maintenance\Models;

use App\Domains\Maintenance\Enums\MaintenanceCategory;
use App\Domains\Maintenance\Enums\MaintenancePriority;
use App\Domains\Maintenance\Enums\MaintenanceStatus;
use App\Domains\Room\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTicket extends Model
{
    protected $fillable = [
        'ticket_number', 'room_id', 'location', 'category', 'priority', 'description',
        'reported_by', 'assigned_to', 'status', 'blocks_room', 'room_status_before_block',
        'started_at', 'resolved_at', 'verified_at', 'verified_by', 'resolution',
        'before_photo', 'after_photo',
    ];

    protected function casts(): array
    {
        return [
            'category' => MaintenanceCategory::class,
            'priority' => MaintenancePriority::class,
            'status' => MaintenanceStatus::class,
            'blocks_room' => 'boolean',
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by')->withDefault(['name' => 'System']);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(fn (MaintenanceStatus $status) => $status->value, MaintenanceStatus::active()));
    }
}
