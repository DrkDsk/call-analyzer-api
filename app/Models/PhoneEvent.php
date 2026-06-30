<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneEvent extends Model
{
    protected $fillable = [
        'import_id',
        'contact',
        'number',
        'first_seen_at',
        'last_seen_at',
        'calls_count',
        'messages_count',
        'data_count',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
