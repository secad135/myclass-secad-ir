<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\LoginLog;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        // ذخیره اطلاعات ورود کاربر در دیتابیس
        LoginLog::create([
            'user_id'    => $event->user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(), // اطلاعات مرورگر و سیستم‌عامل
        ]);
    }
}
