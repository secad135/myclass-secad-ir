<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\ExamService;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    protected ExamService $service;

    public function __construct(ExamService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $this->service->expireStaleAttempts();

        $student = auth()->user();
        $exams = $this->service->getAvailableExamsForStudent($student);

        // همه تلاش‌های دانش‌آموز
        $allAttempts = \App\Models\ExamAttempt::where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'expired', 'in_progress'])
            ->get();

        $attemptsByExam = $allAttempts->whereIn('status', ['submitted', 'expired'])
            ->groupBy('exam_id');

        $inProgressByExam = $allAttempts->where('status', 'in_progress')
            ->keyBy('exam_id');

        $pastAttempts = \App\Models\ExamAttempt::with('exam')
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'expired'])
            ->latest('finished_at')
            ->take(15)
            ->get();

        return view('panel.exams.index', compact('exams', 'attemptsByExam', 'inProgressByExam', 'pastAttempts'));
    }

    public function start(Exam $exam)
    {
        $student = auth()->user();

        // اگر آزمون در حال انجام دارد، به همان برگردد
        $inProgress = $exam->attempts()
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgress) {
            return redirect()->route('panel.exams.show', $inProgress);
        }

        try {
            $attempt = $this->service->createAttempt($exam, $student);
            return redirect()->route('panel.exams.show', $attempt);
        } catch (\Exception $e) {
            return redirect()->route('panel.exams.index')->with('error', $e->getMessage());
        }
    }

    public function show(ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);

        if ($attempt->status === 'submitted' || $attempt->status === 'expired') {
            return redirect()->route('panel.exams.result', $attempt);
        }

        if ($attempt->isExpired()) {
            $this->service->submitAttempt($attempt, [], 'expired');
            return redirect()->route('panel.exams.result', $attempt)
                ->with('warning', 'زمان آزمون به پایان رسیده بود.');
        }

        // اگر بیش از ۹۰ ثانیه از آخرین فعالیت گذشته، یعنی تب بسته بوده
        if ($attempt->status === 'in_progress' && now()->diffInSeconds($attempt->updated_at, true) > 90) {
            $this->service->submitAttempt($attempt, [], 'expired');
            return redirect()->route('panel.exams.result', $attempt)
                ->with('warning', 'شما از صفحه آزمون خارج شده بودید. آزمون به صورت خودکار ثبت شد.');
        }

        $attempt->load(['exam', 'answers.question.options']);
        $remaining = $attempt->remainingSeconds();

        return view('panel.exams.show', compact('attempt', 'remaining'));
    }

    public function submit(Request $request, ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);
        abort_unless($attempt->status === 'in_progress', 404);

        // بررسی زمان (۶۰ ثانیه فرصت برای کندی شبکه)
        $remaining = $attempt->remainingSeconds();
        if ($remaining < -60) {
            $this->service->submitAttempt($attempt, [], 'expired');

            return redirect()->route('panel.exams.result', $attempt)
                ->with('warning', 'زمان آزمون به پایان رسیده بود.');
        }

        $answers = $request->input('answers', []);
        $attempt = $this->service->submitAttempt($attempt, $answers);

        return redirect()->route('panel.exams.result', $attempt)
            ->with('success', 'آزمون شما با موفقیت ثبت شد.');
    }

    public function result(ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);
        abort_unless($attempt->status === 'submitted', 404);

        $attempt->load(['exam', 'answers.question.options', 'answers.selectedOption']);

        return view('panel.exams.result', compact('attempt'));
    }

    public function forceSubmit(ExamAttempt $attempt)
    {
        // فقط صاحب همان آزمون اجازه ثبت اجباری دارد
        abort_unless($attempt->student_id === auth()->id(), 403);

        // اگر آزمون همچنان در حال انجام است، آن را به عنوان "پایان زمان/خروج" ثبت کن
        if ($attempt->status === 'in_progress') {
            $this->service->submitAttempt($attempt, [], 'expired');
        }

        return response()->json(['ok' => true]);
    }

    public function logCheat(ExamAttempt $attempt)
    {
        // فقط صاحب همان آزمون اجازه ثبت تخلف دارد
        abort_unless($attempt->student_id === auth()->id(), 403);

        if ($attempt->status === 'in_progress') {
            $attempt->increment('tab_switches');
        }
        return response()->json(['ok' => true]);
    }

    public function ping(ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);
        
        if ($attempt->status === 'in_progress') {
            $attempt->touch(); // فقط updated_at را به‌روز می‌کند
        }
        
        return response()->json(['ok' => true]);
    }

    public function check(ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);
        
        // اگر بیش از ۹۰ ثانیه از آخرین ضربان گذشته، آزمون را اجباری ثبت کن
        if ($attempt->status === 'in_progress' && now()->diffInSeconds($attempt->updated_at, true) > 90) {
            $this->service->submitAttempt($attempt, [], 'expired');
        }
        
        return response()->json(['status' => $attempt->fresh()->status]);
    }
}
