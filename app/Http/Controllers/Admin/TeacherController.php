<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentCredentialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = User::teachers()
            ->withCount(['classSubjectsAsTeacher', 'homeroomClasses', 'createdExams', 'createdExercises'])
            ->orderBy('name')
            ->get();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create()
    {
        return view('admin.teachers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
        ], [
            'name.required'  => 'نام الزامی است.',
            'email.required' => 'ایمیل الزامی است.',
            'email.email'    => 'فرمت ایمیل صحیح نیست.',
            'email.unique'   => 'این ایمیل قبلاً ثبت شده است.',
        ]);

        $service  = new StudentCredentialService();
        $password = $service->generatePassword();

        $teacher = User::create([
            'name'                 => $request->name,
            'email'                => $request->email,
            'password'             => Hash::make($password),
            'is_teacher'           => true,
            'initial_password'     => $password,
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.teachers.index')
            ->with('success', "معلم «{$teacher->name}» ساخته شد. رمز اولیه: {$password} — معلم در اولین ورود باید رمز را تغییر دهد.");
    }

    public function edit(User $teacher)
    {
        abort_unless($teacher->is_teacher, 404);

        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(Request $request, User $teacher)
    {
        abort_unless($teacher->is_teacher, 404);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher->id)],
        ], [
            'name.required'  => 'نام الزامی است.',
            'email.required' => 'ایمیل الزامی است.',
            'email.email'    => 'فرمت ایمیل صحیح نیست.',
            'email.unique'   => 'این ایمیل قبلاً ثبت شده است.',
        ]);

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
        ];

        if ($teacher->email !== $request->email) {
            $data['email_verified_at'] = null;
        }

        $teacher->update($data);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'اطلاعات معلم ویرایش شد.');
    }

    public function resetPassword(User $teacher)
    {
        abort_unless($teacher->is_teacher, 404);

        $service     = new StudentCredentialService();
        $newPassword = $service->generatePassword();

        $teacher->update([
            'password'             => Hash::make($newPassword),
            'initial_password'     => $newPassword,
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.teachers.index')
            ->with('success', "رمز جدید برای «{$teacher->name}» تنظیم شد: {$newPassword}");
    }

    public function destroy(User $teacher)
    {
        abort_unless($teacher->is_teacher, 404);

        $hasClasses = $teacher->classSubjectsAsTeacher()->exists();
        $hasHomeroom = $teacher->homeroomClasses()->exists();
        $hasExams = $teacher->createdExams()->exists();
        $hasExercises = $teacher->createdExercises()->exists();

        if ($hasClasses || $hasHomeroom || $hasExams || $hasExercises) {
            return redirect()->route('admin.teachers.index')
                ->with('error', 'این معلم به کلاس/درس یا آزمون/تمرین متصل است. ابتدا اتصال‌ها را بردارید یا محتوای او را به معلم دیگری منتقل کنید.');
        }

        $teacher->delete();

        return redirect()->route('admin.teachers.index')
            ->with('success', 'معلم حذف شد.');
    }
}
