<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    protected $fillable = [
        'original_filename',
        'stored_path',
        'file_size',
        'mime_type',
        'status',
        'total_rows',
        'processed_rows',
        'progress',
        'summary',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function phoneEvents(): HasMany
    {
        return $this->hasMany(PhoneEvent::class);
    }
}
