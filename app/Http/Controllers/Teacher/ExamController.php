<?php

namespace App\Http\Controllers\Teacher;

use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Topic;
use App\Services\ExamService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends BaseController
{
    public function index()
    {
        $classSubjectIds = $this->ownClassSubjectIds();

        $exams = Exam::with(['classSubject.subject', 'classSubject.schoolClass.school'])
            ->whereIn('class_subject_id', $classSubjectIds)
            ->withCount('attempts')
            ->latest()
            ->paginate(15);

        return view('admin.exams.index', compact('exams'));
    }

    public function create()
    {
        $classSubjects = $this->ownClassSubjectsForForm();
        $topics = $this->ownTopicsForForm();

        return view('admin.exams.create', compact('classSubjects', 'topics'));
    }

    public function store(Request $request)
    {
        $data = $this->validateExam($request);

        DB::transaction(function () use ($data, $request) {
            $exam = Exam::create([
                'class_subject_id'        => $data['class_subject_id'],
                'created_by'              => auth()->id(),
                'title'                   => $data['title'],
                'description'             => $data['description'] ?? null,
                'question_count'          => $data['question_count'],
                'duration_minutes'        => $data['duration_minutes'],
                'start_at'                => $this->combineDateTime($request->start_date, $request->start_time),
                'end_at'                  => $this->combineDateTime($request->end_date, $request->end_time),
                'shuffle_options'         => $request->boolean('shuffle_options'),
                'show_result_immediately' => $request->boolean('show_result_immediately'),
                'max_attempts'            => $data['max_attempts'],
                'is_active'               => $request->boolean('is_active', true),
            ]);

            $exam->topics()->sync($data['topics']);
        });

        ActivityLogger::log(auth()->user(), 'exam_create', 'آزمون «' . $data['title'] . '» را ساخت.');

        return redirect()->route('teacher.exams.index')
            ->with('success', 'آزمون ساخته شد.');
    }

    public function edit(Exam $exam)
    {
        $this->assertOwnsExam($exam);

        $exam->load('topics');

        $classSubjects = $this->ownClassSubjectsForForm();
        $topics = $this->ownTopicsForForm();

        return view('admin.exams.edit', compact('exam', 'classSubjects', 'topics'));
    }

    public function update(Request $request, Exam $exam)
    {
        $this->assertOwnsExam($exam);

        $data = $this->validateExam($request, $exam->id);

        DB::transaction(function () use ($data, $request, $exam) {
            $exam->update([
                'class_subject_id'        => $data['class_subject_id'],
                'title'                   => $data['title'],
                'description'             => $data['description'] ?? null,
                'question_count'          => $data['question_count'],
                'duration_minutes'        => $data['duration_minutes'],
                'start_at'                => $this->combineDateTime($request->start_date, $request->start_time),
                'end_at'                  => $this->combineDateTime($request->end_date, $request->end_time),
                'shuffle_options'         => $request->boolean('shuffle_options'),
                'show_result_immediately' => $request->boolean('show_result_immediately'),
                'max_attempts'            => $data['max_attempts'],
                'is_active'               => $request->boolean('is_active'),
            ]);

            $exam->topics()->sync($data['topics']);
        });

        ActivityLogger::log(auth()->user(), 'exam_update', 'آزمون «' . $data['title'] . '» را ویرایش کرد.');

        return redirect()->route('teacher.exams.index')
            ->with('success', 'آزمون ویرایش شد.');
    }

    public function destroy(Exam $exam)
    {
        $this->assertOwnsExam($exam);

        $title = $exam->title;
        $exam->delete();

        ActivityLogger::log(auth()->user(), 'exam_delete', 'آزمون «' . $title . '» را حذف کرد.');

        return redirect()->route('teacher.exams.index')
            ->with('success', 'آزمون حذف شد.');
    }

    public function results(Exam $exam)
    {
        $this->assertOwnsExam($exam);

        $service = new ExamService();
        $service->expireStaleAttempts();

        $summary = $service->getExamResultsSummary($exam);
        $analysis = $service->getQuestionAnalysis($exam);

        $exam->load(['classSubject.subject', 'classSubject.schoolClass.school']);

        return view('admin.exams.results', compact('exam', 'summary', 'analysis'));
    }

    public function attemptDetail(Exam $exam, ExamAttempt $attempt)
    {
        $this->assertOwnsExam($exam);
        abort_unless($attempt->exam_id === $exam->id, 404);

        $service = new ExamService();
        $service->expireStaleAttempts();

        $attempt->load(['student', 'exam', 'answers.question.options', 'answers.selectedOption']);
        $exam->load(['classSubject.subject']);

        return view('admin.exams.attempt-detail', compact('exam', 'attempt'));
    }

    public function approveScore(Request $request, ExamAttempt $attempt)
    {
        $attempt->loadMissing('exam');
        $this->assertOwnsExam($attempt->exam);

        $request->validate([
            'teacher_score' => 'required|numeric|min:0|max:20',
        ]);

        $attempt->update([
            'teacher_score' => $request->teacher_score,
            'is_approved'   => true,
        ]);

        $attempt->loadMissing('student');
        ActivityLogger::log(
            auth()->user(),
            'exam_approve',
            'نمره ' . ($attempt->student?->name ?? 'دانش‌آموز') . ' در آزمون «' . ($attempt->exam?->title ?? '—') . '» را تأیید کرد.'
        );

        return back()->with('success', 'نمره تایید و ثبت شد.');
    }

    private function ownClassSubjectsForForm()
    {
        return ClassSubject::with(['subject', 'schoolClass.school', 'schoolClass.academicYear'])
            ->where('teacher_id', $this->teacherId())
            ->whereHas('subject')
            ->get();
    }

    private function ownTopicsForForm()
    {
        $subjectIds = $this->ownSubjectIds();

        return Topic::with('subject')
            ->whereIn('subject_id', $subjectIds)
            ->withCount(['questions' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('subject_id')
            ->orderBy('name')
            ->get();
    }

    private function validateExam(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'class_subject_id' => 'required|exists:class_subjects,id',
            'topics'           => 'required|array|min:1',
            'topics.*'         => 'exists:topics,id',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:2000',
            'question_count'   => 'required|integer|min:1|max:100',
            'duration_minutes' => 'required|integer|min:1|max:300',
            'start_date'       => 'nullable|string',
            'start_time'       => 'nullable|string',
            'end_date'         => 'nullable|string',
            'end_time'         => 'nullable|string',
            'max_attempts'     => 'required|integer|min:1|max:5',
        ], [
            'class_subject_id.required' => 'کلاس-درس را انتخاب کنید.',
            'topics.required'           => 'حداقل یک مبحث انتخاب کنید.',
            'title.required'            => 'عنوان آزمون الزامی است.',
            'question_count.required'   => 'تعداد سوال را وارد کنید.',
            'duration_minutes.required' => 'مدت آزمون را وارد کنید.',
        ]);

        $classSubject = ClassSubject::where('teacher_id', $this->teacherId())
            ->find($data['class_subject_id']);

        abort_unless($classSubject, 403);

        $subjectIds = $this->ownSubjectIds();
        $topicSubjects = Topic::whereIn('id', $data['topics'])->pluck('subject_id')->unique();

        if ($topicSubjects->diff($subjectIds)->isNotEmpty()
            || $topicSubjects->count() > 1
            || (int) $topicSubjects->first() !== (int) $classSubject->subject_id) {
            abort(redirect()->back()->withErrors([
                'topics' => 'همه مباحث باید از درس «' . $classSubject->subject?->name . '» و از درس‌های شما باشند.'
            ])->withInput());
        }

        $availableCount = Question::active()
            ->whereIn('topic_id', $data['topics'])
            ->count();

        if ($availableCount < $data['question_count']) {
            abort(redirect()->back()->withErrors([
                'question_count' => "فقط {$availableCount} سوال فعال در مباحث انتخابی وجود دارد. تعداد سوال آزمون را کم کنید یا سوال بیشتری اضافه کنید."
            ])->withInput());
        }

        return $data;
    }

    private function combineDateTime(?string $jalaliDate, ?string $time): ?string
    {
        if (!$jalaliDate || !$time) {
            return null;
        }

        $gregorian = to_gregorian($jalaliDate);
        if (!$gregorian) {
            return null;
        }

        return $gregorian . ' ' . $time . ':00';
    }
}
