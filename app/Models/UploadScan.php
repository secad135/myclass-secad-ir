<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadScan extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'sha256', 'original_name', 'extension', 'mime', 'size',
        'context', 'user_id', 'verdict', 'reasons', 'engines', 'ip',
    ];

    protected $casts = [
        'reasons'    => 'array',
        'engines'    => 'array',
        'size'       => 'integer',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
