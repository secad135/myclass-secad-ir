<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'name.required'  => 'نام الزامی است.',
            'email.required' => 'ایمیل الزامی است.',
            'email.email'    => 'فرمت ایمیل صحیح نیست.',
            'email.unique'   => 'این ایمیل قبلاً ثبت شده است.',
        ]);

        $data = ['name' => $request->name, 'email' => $request->email];

        if ($user->email !== $request->email) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        return back()->with('success', 'اطلاعات حساب شما به‌روزرسانی شد.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'min:8', 'confirmed'],
        ], [
            'current_password.current_password' => 'رمز فعلی اشتباه است.',
            'password.min'                      => 'رمز جدید باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed'                => 'تکرار رمز جدید مطابقت ندارد.',
        ]);

        $request->user()->update([
            'password'             => Hash::make($validated['password']),
            'must_change_password' => false,
            'initial_password'     => null,
        ]);

        return back()->with('success', 'رمز عبور شما با موفقیت تغییر کرد.');
    }
}
