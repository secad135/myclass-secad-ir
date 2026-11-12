<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public static function cleanup(int $days = 7): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
