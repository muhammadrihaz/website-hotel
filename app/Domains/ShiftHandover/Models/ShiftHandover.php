<?php

namespace App\Domains\ShiftHandover\Models;

use App\Domains\ShiftHandover\Enums\ShiftHandoverStatus;
use App\Domains\ShiftHandover\Enums\ShiftType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftHandover extends Model
{
    protected $fillable = [
        'shift_date', 'shift_type', 'created_by', 'handover_to', 'notes', 'status',
        'handed_over_at', 'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'shift_date' => 'date',
            'shift_type' => ShiftType::class,
            'status' => ShiftHandoverStatus::class,
            'handed_over_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(['name' => 'System']);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handover_to');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShiftHandoverItem::class);
    }
}
