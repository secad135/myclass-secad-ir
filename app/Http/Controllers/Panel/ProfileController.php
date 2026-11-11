<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('panel.profile', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'email.required' => 'ایمیل الزامی است.',
            'email.email'    => 'فرمت ایمیل صحیح نیست.',
            'email.unique'   => 'این ایمیل قبلاً برای کاربر دیگری ثبت شده است.',
        ]);

        $data = ['email' => $request->email];

        if ($user->email !== $request->email) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        // بعد از این خط:
        $user->update($data);

        // این را اضافه کنید:
        \Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'ایمیل شما تغییر کرد. لطفاً با ایمیل جدید وارد شوید.');

        return redirect()->route('panel.profile.edit')
            ->with('success', 'ایمیل شما با موفقیت به‌روزرسانی شد.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'min:8', 'confirmed'],
        ], [
            'current_password.required'         => 'رمز فعلی را وارد کنید.',
            'current_password.current_password' => 'رمز فعلی اشتباه است.',
            'password.required'                 => 'رمز جدید را وارد کنید.',
            'password.min'                      => 'رمز جدید باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed'                => 'تکرار رمز جدید مطابقت ندارد.',
        ]);

        $user = $request->user();
        $wasForcedChange = $user->must_change_password;

        $user->update([
            'password'             => Hash::make($validated['password']),
            'must_change_password' => false,
            'initial_password'     => null,
        ]);

        if ($wasForcedChange) {
            return redirect()->route('panel.dashboard')
                ->with('success', 'رمز شما تغییر کرد. به پنل خوش آمدید!');
        }

        return redirect()->route('panel.profile.edit')
            ->with('success', 'رمز شما با موفقیت تغییر کرد.');
    }
}
