<?php

namespace App\Domains\System\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = [
        'document_type',
        'sequence_date',
        'current_value',
    ];

    protected function casts(): array
    {
        return [
            'sequence_date' => 'date',
            'current_value' => 'integer',
        ];
    }
}
