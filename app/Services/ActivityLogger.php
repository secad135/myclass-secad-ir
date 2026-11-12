<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ActivityLogger
{
    public static function log(?User $user, string $action, string $description): void
    {
        if (!$user || !$user->is_student || !Schema::hasTable('activity_logs')) {
            return;
        }

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => $action,
            'description' => $description,
        ]);
    }
}
