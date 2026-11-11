<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Services\StudentCredentialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ManagerController extends Controller
{
    public function index()
    {
        $managers = User::where('is_school_manager', true)->with('school')->latest()->get();
        return view('admin.managers.index', compact('managers'));
    }

    public function create()
    {
        $schools = School::orderBy('name')->get();
        return view('admin.managers.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8',
            'school_id' => 'required|exists:schools,id',
        ]);

        User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'is_school_manager' => true,
            'school_id'         => $request->school_id,
            'initial_password'  => $request->password,
        ]);

        return redirect()->route('admin.managers.index')
            ->with('success', 'مسئول مدرسه ساخته شد.');
    }

    public function edit(User $manager)
    {
        abort_unless($manager->is_school_manager, 404);
        $schools = School::orderBy('name')->get();
        return view('admin.managers.edit', compact('manager', 'schools'));
    }

    public function update(Request $request, User $manager)
    {
        abort_unless($manager->is_school_manager, 404);

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($manager->id)],
            'school_id' => 'required|exists:schools,id',
        ]);

        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'school_id' => $request->school_id,
        ];

        if ($manager->email !== $request->email) {
            $data['email_verified_at'] = null;
        }

        $manager->update($data);

        return redirect()->route('admin.managers.index')
            ->with('success', 'مسئول مدرسه ویرایش شد.');
    }

    public function resetPassword(User $manager)
    {
        abort_unless($manager->is_school_manager, 404);

        $service = new StudentCredentialService();
        $newPassword = $service->generatePassword();

        $manager->update([
            'password'         => Hash::make($newPassword),
            'initial_password' => $newPassword,
        ]);

        return redirect()->route('admin.managers.index')
            ->with('success', "رمز جدید برای «{$manager->name}» تنظیم شد: {$newPassword}");
    }

    public function destroy(User $manager)
    {
        abort_unless($manager->is_school_manager, 404);
        $manager->delete();
        return redirect()->route('admin.managers.index')
            ->with('success', 'مسئول مدرسه حذف شد.');
    }
}
