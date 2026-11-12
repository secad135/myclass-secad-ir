<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\PointCategoryController;
use App\Http\Controllers\Admin\TopicController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/help', 'help')->name('help');

Route::middleware('auth')->get('/dashboard', function () {
    $user = auth()->user();

    if ($user->is_admin) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->is_student) {
        return redirect()->route('panel.dashboard');
    }

    return view('dashboard');
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

// ==================== پنل مدیریت ====================
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('login-logs', [\App\Http\Controllers\Admin\LoginLogController::class, 'index'])->name('login-logs.index');

    Route::get('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile');
    Route::patch('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password');

    // --- مدیریت پایه ---
    Route::resource('grades', GradeController::class);
    Route::resource('academic-years', AcademicYearController::class);
    Route::resource('schools', SchoolController::class);
    Route::resource('subjects', SubjectController::class);
    Route::resource('point-categories', PointCategoryController::class);

    // --- دانش‌آموزان ---
    Route::get('students/credentials', [StudentController::class, 'credentials'])->name('students.credentials');
    Route::post('students/{student}/reset-password', [StudentController::class, 'resetPassword'])->name('students.reset-password');
    Route::resource('students', StudentController::class);

    // --- کلاس‌ها ---
    Route::post('classes/{class}/enroll/{student?}', [SchoolClassController::class, 'enroll'])->name('classes.enroll');
    Route::delete('classes/{class}/unenroll/{student}', [SchoolClassController::class, 'unenroll'])->name('classes.unenroll');
    Route::post('classes/{class}/subjects', [SchoolClassController::class, 'addSubject'])->name('classes.subjects.add');
    Route::delete('classes/{class}/subjects/{classSubject}', [SchoolClassController::class, 'removeSubject'])->name('classes.subjects.remove');
    Route::put('classes/{class}/subjects/{classSubject}/days', [SchoolClassController::class, 'updateSubjectDays'])->name('classes.subjects.days.update');
    Route::get('classes/{class}/report', [SchoolClassController::class, 'report'])->name('classes.report');
    Route::get('classes/{class}/full-report', [SchoolClassController::class, 'reportFull'])->name('classes.full-report');
    Route::resource('classes', SchoolClassController::class);

    // --- حضور و غیاب ---
    Route::get('attendance/{classSubject}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('attendance/{classSubject}/start', [AttendanceController::class, 'start'])->name('attendance.start');
    Route::post('attendance/{classSubject}/save', [AttendanceController::class, 'save'])->name('attendance.save');
    Route::delete('attendance/{classSubject}/day/{day}', [AttendanceController::class, 'destroyDay'])->name('attendance.destroy-day');
    Route::post('attendance/{classSubject}/points', [AttendanceController::class, 'addPoint'])->name('attendance.points.add');
    Route::delete('attendance/{classSubject}/points/{pointEntry}', [AttendanceController::class, 'deletePoint'])->name('attendance.points.delete');

    // --- مباحث ---
    Route::resource('topics', TopicController::class);

    // --- سوالات ---
    // ⚠️ این سه خط باید قبل از resource باشند
    Route::get('questions/import', [QuestionController::class, 'importForm'])->name('questions.import');
    Route::post('questions/import', [QuestionController::class, 'import'])->name('questions.import.store');
    Route::get('questions/template', [QuestionController::class, 'downloadTemplate'])->name('questions.template');

    Route::resource('questions', QuestionController::class);

    // --- آزمون‌ها ---
    Route::get('exams/{exam}/results', [\App\Http\Controllers\Admin\ExamController::class, 'results'])->name('exams.results');
    Route::get('exams/{exam}/attempts/{attempt}', [\App\Http\Controllers\Admin\ExamController::class, 'attemptDetail'])->name('exams.attempt-detail');
    Route::delete('exams/{exam}/attempts/{attempt}', [\App\Http\Controllers\Admin\ExamController::class, 'destroyAttempt'])->name('exams.attempts.destroy');
    Route::post('exams/attempts/{attempt}/approve', [\App\Http\Controllers\Admin\ExamController::class, 'approveScore'])->name('exams.approve-score');

    Route::resource('exams', \App\Http\Controllers\Admin\ExamController::class);

    // --- تمرین‌ها ---
    Route::get('exercises/{exercise}/submissions', [\App\Http\Controllers\Admin\ExerciseController::class, 'submissions'])->name('exercises.submissions');
    Route::post('exercises/{exercise}/submissions/{submission}/grade', [\App\Http\Controllers\Admin\ExerciseController::class, 'grade'])->name('exercises.grade');

    Route::resource('exercises', \App\Http\Controllers\Admin\ExerciseController::class);

    // --- مسئولان مدرسه ---
    Route::post('managers/{manager}/reset-password', [\App\Http\Controllers\Admin\ManagerController::class, 'resetPassword'])->name('managers.reset-password');
    Route::resource('managers', \App\Http\Controllers\Admin\ManagerController::class);
});

// ==================== پنل مسئول مدرسه ====================
Route::middleware(['auth', 'manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Manager\DashboardController::class, 'index'])->name('dashboard');

    // پروفایل مسئول مدرسه
    Route::get('profile', [\App\Http\Controllers\Manager\ProfileController::class, 'edit'])->name('profile');
    Route::patch('profile', [\App\Http\Controllers\Manager\ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [\App\Http\Controllers\Manager\ProfileController::class, 'updatePassword'])->name('profile.password');

    // گزارش کلاس‌ها
    Route::get('classes/{class}/report', [\App\Http\Controllers\Manager\ReportController::class, 'classReport'])->name('classes.report');

    // گزارش کامل کلاس برای چاپ
    Route::get('classes/{class}/full-report', [\App\Http\Controllers\Manager\ReportController::class, 'fullReport'])->name('classes.full-report');

    // پروفایل دانش‌آموز
    Route::get('students/{student}', [\App\Http\Controllers\Manager\ReportController::class, 'studentProfile'])->name('students.show');
});

// ==================== پنل دانش‌آموز ====================
Route::middleware(['auth', 'student', 'password.changed'])
    ->prefix('panel')
    ->name('panel.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Panel\DashboardController::class, 'index'])->name('dashboard');
        Route::get('attendance', [\App\Http\Controllers\Panel\AttendanceController::class, 'index'])->name('attendance');
        Route::get('points', [\App\Http\Controllers\Panel\PointsController::class, 'index'])->name('points');

        Route::get('exams', [\App\Http\Controllers\Panel\ExamController::class, 'index'])->name('exams.index');
        Route::post('exams/{exam}/start', [\App\Http\Controllers\Panel\ExamController::class, 'start'])->name('exams.start');
        Route::get('exams/attempt/{attempt}', [\App\Http\Controllers\Panel\ExamController::class, 'show'])->name('exams.show');
        Route::post('exams/attempt/{attempt}/submit', [\App\Http\Controllers\Panel\ExamController::class, 'submit'])->name('exams.submit');
        Route::post('exams/attempt/{attempt}/force-submit', [\App\Http\Controllers\Panel\ExamController::class, 'forceSubmit'])->name('exams.force-submit');
        Route::post('exams/attempt/{attempt}/log-cheat', [\App\Http\Controllers\Panel\ExamController::class, 'logCheat'])->name('exams.log-cheat');
        Route::post('exams/attempt/{attempt}/ping', [\App\Http\Controllers\Panel\ExamController::class, 'ping'])->name('exams.ping');
        Route::get('exams/attempt/{attempt}/check', [\App\Http\Controllers\Panel\ExamController::class, 'check'])->name('exams.check');
        Route::get('exams/attempt/{attempt}/result', [\App\Http\Controllers\Panel\ExamController::class, 'result'])->name('exams.result');

        Route::get('exercises', [\App\Http\Controllers\Panel\ExerciseController::class, 'index'])->name('exercises.index');
        Route::get('exercises/{exercise}', [\App\Http\Controllers\Panel\ExerciseController::class, 'show'])->name('exercises.show');
        Route::post('exercises/{exercise}/submit', [\App\Http\Controllers\Panel\ExerciseController::class, 'submit'])->name('exercises.submit');

        Route::get('profile', [\App\Http\Controllers\Panel\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [\App\Http\Controllers\Panel\ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [\App\Http\Controllers\Panel\ProfileController::class, 'updatePassword'])->name('profile.password');
    });
