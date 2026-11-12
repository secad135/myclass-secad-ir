<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        // ۱. آمار کلی
        $stats = [
            'schools'  => School::count(),
            'classes'  => SchoolClass::count(),
            'students' => User::students()->count(),
            'years'    => AcademicYear::count(),
        ];

        $currentYear = AcademicYear::where('is_current', true)->first();

        // ۲. درس‌های کلاس‌ها (برای دکمه ثبت حضور و غیاب)
        // فقط کلاس‌هایی که در سال تحصیلی جاری هستند
        $classSubjects = ClassSubject::with(['subject', 'schoolClass'])
            ->whereHas('schoolClass', function($q) use ($currentYear) {
                if ($currentYear) {
                    $q->where('academic_year_id', $currentYear->id);
                }
            })
            ->get();

        // ۳. آزمون‌های اخیر
        $recentExams = Exam::with('classSubject.subject')
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', now());
            })
            ->latest()
            ->take(3)
            ->get();

        // ۴. تمرین‌های اخیر
        $recentExercises = Exercise::with('classSubject.subject')
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('deadline_at')->orWhere('deadline_at', '>=', now());
            })
            ->latest()
            ->take(3)
            ->get();

        // ۵. پاسخ‌های در انتظار تصحیح (تمرین‌ها)
        $pendingSubmissionsCount = ExerciseSubmission::whereNull('graded_at')
            ->whereHas('exercise', fn($q) => $q->where('is_active', true))
            ->count();

        // پاکسازی لاگ‌های قدیمی‌تر از ۷ روز (خودکار در هر بار لود داشبورد)
        $recentActivities = collect();

        if (Schema::hasTable('activity_logs')) {
            \App\Models\ActivityLog::cleanup(7);

            // آخرین ۳۰ فعالیت دانش‌آموزان در ۷ روز اخیر
            $recentActivities = \App\Models\ActivityLog::with('user')
                ->recent(7)
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();
        }

        return view('admin.dashboard', compact(
            'stats', 'currentYear', 'classSubjects',
            'recentExams', 'recentExercises', 'pendingSubmissionsCount',
            'recentActivities'
        ));
    }
}