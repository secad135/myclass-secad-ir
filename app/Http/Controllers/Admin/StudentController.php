<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\AcademicYear;
use App\Services\StudentReportService;
use App\Services\StudentCredentialService;

class StudentController extends Controller
{
    public function show(Request $request, User $student)
    {
        abort_unless($student->is_student, 404);

        $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();
        if (!$currentYear) {
            return redirect()->route('admin.academic-years.index')
                ->with('success', 'ابتدا یک سال تحصیلی «جاری» تعریف کنید.');
        }

        $service = new \App\Services\StudentReportService();

        $quickFilter = $request->input('quick');
        $fromInput   = $request->input('from');
        $toInput     = $request->input('to');

        [$fromDate, $toDate] = $service->resolveDateRange($fromInput, $toInput, $quickFilter, $currentYear);

        $overall         = $service->getOverallStats($student, $fromDate, $toDate);
        $bySubject       = $service->getStatsByClassSubject($student, $fromDate, $toDate);
        $points          = $service->getPointsSummary($student, $fromDate, $toDate);
        $absences        = $service->getAbsenceDetails($student, $fromDate, $toDate);
        $groupedAttendance = $service->getGroupedAttendanceByClassSubject($student, $fromDate, $toDate); // ← جدید
        $status          = $service->getStatusLabel($overall['percentage']);
        $examResults     = $service->getExamResultsForStudent($student);
        $exerciseResults = $service->getExerciseResultsForStudent($student); // ← جدید

        return view('admin.students.show', compact(
            'student', 'currentYear',
            'fromDate', 'toDate', 'quickFilter',
            'overall', 'bySubject', 'points', 'absences', 'status',
            'examResults', 'exerciseResults', 'groupedAttendance'
        ));
    }

    public function index()
    {
        $students = User::students()
            ->withCount('enrollments')
            ->orderBy('id', 'asc')
            ->get();

        return view('admin.students.index', compact('students'));
    }

    public function create()
    {
        return view('admin.students.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $service  = new StudentCredentialService();
        $password = $service->generatePassword();

        // ۱. ساخت با ایمیل موقت
        $student = User::create([
            'name'                 => $request->name,
            'email'                => 'tmp_' . uniqid() . '@myclass.ir',
            'password'             => $password,
            'is_student'           => true,
            'initial_password'     => $password,
            'must_change_password' => true,
        ]);

        // ۲. تنظیم ایمیل سیستماتیک بر اساس ID
        $student->email = $service->generateEmail($student->id);
        $student->save();

        return redirect()->route('admin.students.index')
            ->with('success', "دانش‌آموز «{$student->name}» ساخته شد. رمز اولیه: {$password} — از برگه اطلاعات ورود قابل مشاهده است.");
    }

   public function edit(User $student)
{
    abort_unless($student->is_student, 404);
    return view('admin.students.edit', compact('student'));
}

    public function update(Request $request, User $student)
    {
        abort_unless($student->is_student, 404);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $student->update(['name' => $request->name]);

        return redirect()->route('admin.students.index')
            ->with('success', 'نام دانش‌آموز ویرایش شد.');
    }

    public function destroy(User $student)
    {
        abort_unless($student->is_student, 404);
        $student->delete();

        return redirect()->route('admin.students.index')
            ->with('success', 'دانش‌آموز حذف شد.');
    }
    /**
     * صفحه برگه اطلاعات ورود همه دانش‌آموزان
     */
    public function credentials()
    {
        $students = User::students()
            ->orderBy('name')
            ->get();

        return view('admin.students.credentials', compact('students'));
    }

    /**
     * بازنشانی رمز یک دانش‌آموز
     */
    public function resetPassword(User $student)
    {
        abort_unless($student->is_student, 404);

        $service     = new StudentCredentialService();
        $newPassword = $service->generatePassword();

        $student->update([
            'password'             => $newPassword,
            'initial_password'     => $newPassword,
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.students.credentials')
            ->with('success', "رمز «{$student->name}» بازنشانی شد. رمز جدید: {$newPassword}");
    }

    public function createManager()
    {
        $schools = \App\Models\School::all();
        return view('admin.managers.create', compact('schools'));
    }

    public function storeManager(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'school_id' => 'required|exists:schools,id',
            'password' => 'required|min:8'
        ]);

        \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'is_school_manager' => true,
            'school_id' => $request->school_id,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'مسئول مدرسه ساخته شد.');
    }
}
