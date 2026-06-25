<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = [
        'original_filename',
        'file_size',
        'mime_type',
        'status',
        'total_rows',
        'processed_rows',
        'progress',
        'summary',
        'error_message',
        'started_at',
        'finished_at'
    ];
}
