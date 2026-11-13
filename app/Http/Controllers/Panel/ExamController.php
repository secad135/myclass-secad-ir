<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamEvent;
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

    public function start(Request $request, Exam $exam)
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
            $attempt = $this->service->createAttempt($exam, $student, $this->attemptContext($request));

            $this->logEvent($attempt, 'started', null, $request);

            \App\Services\ActivityLogger::log(
                $student,
                'exam_start',
                'آزمون «' . $exam->title . '» را آغاز کرد.'
            );

            return redirect()->route('panel.exams.show', $attempt);
        } catch (\Exception $e) {
            return redirect()->route('panel.exams.index')->with('error', $e->getMessage());
        }
    }

    public function show(Request $request, ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);

        if ($attempt->status === 'submitted' || $attempt->status === 'expired') {
            return redirect()->route('panel.exams.result', $attempt);
        }

        if ($attempt->isExpired()) {
            try {
                $this->service->submitAttempt($attempt, [], 'expired', $this->attemptContext($request));
            } catch (\Exception $e) {
                // اگر درخواست هم‌زمان دیگری آزمون را بسته باشد
            }

            return redirect()->route('panel.exams.result', $attempt)
                ->with('warning', 'زمان آزمون به پایان رسیده بود.');
        }

        $this->assertAttemptSession($request, $attempt);

        $attempt->load(['exam', 'answers.question.options']);
        $remaining = $attempt->remainingSeconds();

        return view('panel.exams.show', compact('attempt', 'remaining'));
    }

    public function submit(Request $request, ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);
        abort_unless($attempt->status === 'in_progress', 404);

        // فرصت کوتاه برای کندی شبکه
        $secondsLate = now()->timestamp - $attempt->endsAt()->timestamp;
        if ($secondsLate > 10) {
            try {
                $this->service->submitAttempt($attempt, [], 'expired', $this->attemptContext($request));
            } catch (\Exception $e) {
                // اگر درخواست هم‌زمان دیگری آزمون را بسته باشد
            }

            return redirect()->route('panel.exams.result', $attempt)
                ->with('warning', 'زمان آزمون به پایان رسیده بود.');
        }

        $request->validate([
            'answers'   => 'nullable|array',
            'answers.*' => 'nullable|integer',
        ]);

        $this->assertAttemptSession($request, $attempt);

        $answers = $request->input('answers', []);
        try {
            $attempt = $this->service->submitAttempt($attempt, $answers, 'submitted', $this->attemptContext($request));
        } catch (\Exception $e) {
            return redirect()->route('panel.exams.result', $attempt)
                ->with('warning', $e->getMessage());
        }

        \App\Services\ActivityLogger::log(
            auth()->user(),
            'exam_submit',
            'آزمون «' . $attempt->exam->title . '» را ثبت کرد — نمره: ' . $attempt->score
        );

        return redirect()->route('panel.exams.result', $attempt)
            ->with('success', 'آزمون شما با موفقیت ثبت شد.');
    }

    public function save(Request $request, ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);

        if ($attempt->status !== 'in_progress') {
            return response()->json(['status' => $attempt->status], 409);
        }

        $request->validate([
            'answers'   => 'nullable|array',
            'answers.*' => 'nullable|integer',
        ]);

        if ($attempt->isExpired()) {
            try {
                $this->service->submitAttempt($attempt, [], 'expired', $this->attemptContext($request));
            } catch (\Exception $e) {
                // اگر درخواست هم‌زمان دیگری آزمون را بسته باشد
            }

            return response()->json(['status' => 'expired'], 409);
        }

        $this->assertAttemptSession($request, $attempt);

        try {
            $attempt = $this->service->saveAnswers($attempt, $request->input('answers', []));
        } catch (\Exception $e) {
            return response()->json([
                'status' => $attempt->fresh()->status,
                'error'  => $e->getMessage(),
            ], 422);
        }

        return response()->json(['status' => $attempt->status]);
    }

    public function result(Request $request, ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);
        abort_unless(in_array($attempt->status, ['submitted', 'expired'], true), 404);

        $attempt->load([
            'exam',
            'answers.question.options',
            'answers.selectedOption',
            'answers.correctOption',
        ]);

        return view('panel.exams.result', compact('attempt'));
    }

    public function logCheat(Request $request, ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        if ($attempt->status === 'in_progress') {
            $this->assertAttemptSession($request, $attempt);
            $attempt->increment('tab_switches');
            $this->logEvent($attempt, 'client_report', $request->input('reason'), $request);
        }

        return response()->json(['ok' => true]);
    }

    public function ping(Request $request, ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);

        if ($attempt->status === 'in_progress') {
            $this->assertAttemptSession($request, $attempt);

            $gap = $attempt->updated_at
                ? max(0, now()->timestamp - $attempt->updated_at->timestamp)
                : 0;

            if ($gap >= 90) {
                $this->logEvent(
                    $attempt,
                    'heartbeat_gap',
                    "فاصله {$gap} ثانیه‌ای بین ضربان‌های مرورگر",
                    $request,
                    ['gap_seconds' => $gap]
                );
            }

            $attempt->touch(); // فقط updated_at را به‌روز می‌کند
        }

        return response()->json(['ok' => true]);
    }

    public function check(Request $request, ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);

        if ($attempt->status === 'in_progress') {
            $this->assertAttemptSession($request, $attempt);
        }

        return response()->json(['status' => $attempt->fresh()->status]);
    }

    private function attemptContext(Request $request): array
    {
        return [
            'session_id' => $this->currentSessionId($request),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ];
    }

    private function currentSessionId(Request $request): string
    {
        return hash('sha256', $request->session()->getId());
    }

    /**
     * از ادامه آزمون در نشست دیگر جلوگیری می‌کند، اما اجازه اتصال مجدد
     * پس از انقضای نشست قبلی را می‌دهد.
     */
    private function assertAttemptSession(Request $request, ExamAttempt $attempt): void
    {
        $current = $this->currentSessionId($request);

        if (!$attempt->session_id) {
            $attempt->update($this->attemptContext($request));
            $this->logEvent($attempt, 'session_bound', 'اتصال اولیه نشست به تلاش', $request);

            return;
        }

        if (hash_equals($attempt->session_id, $current)) {
            return;
        }

        $recentlyActive = $attempt->updated_at && $attempt->updated_at->gt(now()->subMinutes(2));

        $this->logEvent($attempt, 'session_mismatch', 'درخواست از نشست متفاوت', $request);

        if ($recentlyActive) {
            abort(403, 'این آزمون در نشست دیگری در حال انجام است.');
        }

        $attempt->update($this->attemptContext($request));
        $this->logEvent($attempt, 'session_rebound', 'اتصال مجدد به نشست جدید', $request);
    }

    private function logEvent(ExamAttempt $attempt, string $type, ?string $reason, Request $request, array $metadata = []): void
    {
        ExamEvent::create([
            'exam_attempt_id' => $attempt->id,
            'type'            => $type,
            'reason'          => $reason,
            'ip_address'      => $request->ip(),
            'user_agent'      => substr((string) $request->userAgent(), 0, 1000),
            'metadata'        => $metadata ?: null,
            'occurred_at'     => now(),
        ]);
    }
}
