<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;

class LoginLogController extends Controller
{
    public function index()
    {
        // دریافت لاگ‌ها به همراه اطلاعات کاربر، مرتب‌شده از جدیدترین
        $logs = LoginLog::with('user')->latest()->paginate(30);

        return view('admin.login-logs.index', compact('logs'));
    }
}
