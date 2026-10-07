<?php

namespace App\Domains\Room\Models;

use App\Domains\GuestRequest\Models\GuestRequest;
use App\Domains\Housekeeping\Models\HousekeepingTask;
use App\Domains\Maintenance\Models\MaintenanceTicket;
use App\Domains\Reservation\Models\Reservation;
use App\Domains\Reservation\Models\Stay;
use App\Domains\Room\Enums\HousekeepingStatus;
use App\Domains\Room\Enums\OccupancyStatus;
use App\Domains\Room\Enums\OperationalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'room_number',
        'floor',
        'room_type_id',
        'occupancy_status',
        'housekeeping_status',
        'operational_status',
        'base_rate',
        'capacity_adult',
        'capacity_child',
        'description',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'occupancy_status' => OccupancyStatus::class,
            'housekeeping_status' => HousekeepingStatus::class,
            'operational_status' => OperationalStatus::class,
            'base_rate' => 'decimal:2',
            'capacity_adult' => 'integer',
            'capacity_child' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function stays(): HasMany
    {
        return $this->hasMany(Stay::class);
    }

    public function activeStay(): HasOne
    {
        return $this->hasOne(Stay::class)->whereNull('checked_out_at')->latestOfMany('checked_in_at');
    }

    public function housekeepingTasks(): HasMany
    {
        return $this->hasMany(HousekeepingTask::class);
    }

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class);
    }

    public function guestRequests(): HasMany
    {
        return $this->hasMany(GuestRequest::class);
    }

    public function isReadyForSale(): bool
    {
        return $this->is_active
            && $this->occupancy_status === OccupancyStatus::Vacant
            && $this->housekeeping_status === HousekeepingStatus::Ready
            && $this->operational_status === OperationalStatus::Available;
    }
}
