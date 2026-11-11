<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // بررسی اینکه آیا ایمیل از نوع سیستماتیک (مثل s001@myclass.ir) است یا خیر
        if (str_ends_with($request->email, '@myclass.ir')) {
            return back()->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'ایمیل‌های پیش‌فرض سامانه امکان دریافت لینک بازیابی را ندارند. لطفاً برای بازنشانی رمز عبور، در شبکه شاد به معلم خود پیام دهید.'
                ]);
        }

        // روند عادی لاراول برای ایمیل‌های واقعی (مثل جیمیل معلم یا ادمین)
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                            ->withErrors(['email' => __($status)]);
    }
}
