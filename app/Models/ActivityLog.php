<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
        if (!Schema::hasTable('activity_logs')) {
            return 0;
        }

        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
