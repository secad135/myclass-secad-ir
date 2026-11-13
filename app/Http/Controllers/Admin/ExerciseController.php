<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\UploadRejectedException;
use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Exercise;
use App\Services\Uploads\UploadService;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    public function __construct(private readonly UploadService $uploads)
    {
    }

    public function index()
    {
        $exercises = Exercise::with(['classSubject.subject', 'classSubject.schoolClass'])
            ->withCount('submissions')
            ->latest()
            ->paginate(15);

        return view('admin.exercises.index', compact('exercises'));
    }

    public function create()
    {
        $classSubjects = ClassSubject::with(['subject', 'schoolClass'])
            ->whereHas('subject')
            ->get();

        return view('admin.exercises.create', compact('classSubjects'));
    }

    public function store(Request $request)
    {
        $data = $this->validateExercise($request);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            try {
                $attachmentPath = $this->uploads
                    ->store($request->file('attachment'), 'exercise_attachment', 'exercise-attachments')['path'];
            } catch (UploadRejectedException $e) {
                return back()->withInput()->withErrors(['attachment' => $e->getMessage()]);
            }
        }

        Exercise::create([
            'class_subject_id'       => $data['class_subject_id'],
            'created_by'             => auth()->id(),
            'title'                  => $data['title'],
            'description'            => $data['description'],
            'attachment_path'        => $attachmentPath,
            'deadline_at'            => $this->combineDateTime($request->deadline_date, $request->deadline_time),
            'max_score'              => $data['max_score'],
            'allow_late_submission'  => $request->boolean('allow_late_submission'),
            'is_active'              => $request->boolean('is_active', true),
            'requires_code'          => $request->boolean('requires_code'),
            'allow_file_upload'      => $request->boolean('allow_file_upload'),
        ]);

        return redirect()->route('admin.exercises.index')
            ->with('success', 'تمرین ساخته شد.');
    }

    public function edit(Exercise $exercise)
    {
        $classSubjects = ClassSubject::with(['subject', 'schoolClass'])
            ->whereHas('subject')
            ->get();

        return view('admin.exercises.edit', compact('exercise', 'classSubjects'));
    }

    public function update(Request $request, Exercise $exercise)
    {
        $data = $this->validateExercise($request);

        $attachmentPath = $exercise->attachment_path;
        if ($request->hasFile('attachment')) {
            try {
                $newPath = $this->uploads
                    ->store($request->file('attachment'), 'exercise_attachment', 'exercise-attachments')['path'];
            } catch (UploadRejectedException $e) {
                return back()->withInput()->withErrors(['attachment' => $e->getMessage()]);
            }

            $this->uploads->delete($attachmentPath);
            $attachmentPath = $newPath;
        }

        $exercise->update([
            'class_subject_id'       => $data['class_subject_id'],
            'title'                  => $data['title'],
            'description'            => $data['description'],
            'attachment_path'        => $attachmentPath,
            'deadline_at'            => $this->combineDateTime($request->deadline_date, $request->deadline_time),
            'max_score'              => $data['max_score'],
            'allow_late_submission'  => $request->boolean('allow_late_submission'),
            'is_active'              => $request->boolean('is_active'),
            'requires_code'          => $request->boolean('requires_code'),
            'allow_file_upload'      => $request->boolean('allow_file_upload'),
        ]);

        return redirect()->route('admin.exercises.index')
            ->with('success', 'تمرین ویرایش شد.');
    }

    public function destroy(Exercise $exercise)
    {
        $this->uploads->delete($exercise->attachment_path);
        $exercise->delete();

        return redirect()->route('admin.exercises.index')
            ->with('success', 'تمرین حذف شد.');
    }

    public function submissions(Exercise $exercise)
    {
        $exercise->load(['classSubject.subject', 'classSubject.schoolClass']);

        $students = $exercise->classSubject->schoolClass->students()->orderBy('name')->get();
        $submissions = $exercise->submissions()->get()->keyBy('student_id');

        return view('admin.exercises.submissions', compact('exercise', 'students', 'submissions'));
    }

    public function storeSubmission(Request $request, Exercise $exercise)
    {
        $exercise->loadMissing(['classSubject.schoolClass']);

        $data = $request->validate([
            'student_id'     => 'required|integer',
            'content'        => 'nullable|string|max:10000',
            'code_content'   => 'nullable|string|max:50000',
            'file'           => 'nullable|file|max:10240',
            'submitted_date' => 'nullable|string|max:20',
            'submitted_time' => 'nullable|string|max:10',
        ]);

        $student = $exercise->classSubject->schoolClass->students()
            ->whereKey($data['student_id'])
            ->first();

        abort_unless($student, 404);

        if (!$request->filled('content') && !$request->filled('code_content') && !$request->hasFile('file')) {
            return back()->with('error', 'متن پاسخ یا فایل پاسخ را وارد کنید.');
        }

        $existing = $exercise->submissions()->where('student_id', $student->id)->first();

        if ($existing && $existing->isGraded()) {
            return back()->with('error', 'پاسخ این دانش‌آموز قبلاً تصحیح شده و قابل ویرایش نیست.');
        }

        $submittedAt = $this->resolveSubmittedAt($request);
        $isLate = $request->filled('submitted_date')
            && $exercise->deadline_at
            && $submittedAt->gt($exercise->deadline_at);

        $filePath = $existing?->file_path;

        if ($request->hasFile('file')) {
            try {
                $newPath = $this->uploads
                    ->store($request->file('file'), 'exercise_submission', 'exercise-submissions')['path'];
            } catch (UploadRejectedException $e) {
                return back()->with('error', $e->getMessage());
            }

            $this->uploads->delete($filePath);
            $filePath = $newPath;
        }

        $exercise->submissions()->updateOrCreate(
            ['student_id' => $student->id],
            [
                'content'      => $request->content,
                'code_content' => $request->code_content,
                'file_path'    => $filePath,
                'submitted_at' => $submittedAt,
                'is_late'      => $isLate,
            ]
        );

        \App\Services\ActivityLogger::log(
            auth()->user(),
            'exercise_submission_recorded',
            'پاسخ ' . $student->name . ' در تمرین «' . $exercise->title . '» را به‌صورت دستی ثبت کرد.'
        );

        return redirect()->route('admin.exercises.submissions', $exercise)
            ->with('success', 'پاسخ دانش‌آموز ثبت شد. اکنون می‌توانید نمره بدهید.');
    }

    public function grade(Request $request, Exercise $exercise, \App\Models\ExerciseSubmission $submission)
    {
        abort_unless($submission->exercise_id === $exercise->id, 404);

        $request->validate([
            'score'    => 'required|numeric|min:0|max:' . $exercise->max_score,
            'feedback' => 'nullable|string|max:2000',
        ], [
            'score.required' => 'نمره الزامی است.',
            'score.max'      => "نمره باید حداکثر {$exercise->max_score} باشد.",
        ]);

        $submission->update([
            'score'     => $request->score,
            'feedback'  => $request->feedback,
            'graded_at' => now(),
            'graded_by' => auth()->id(),
        ]);

        return redirect()->route('admin.exercises.submissions', $exercise)
            ->with('success', 'نمره ثبت شد.');
    }

    private function validateExercise(Request $request): array
    {
        return $request->validate([
            'class_subject_id' => 'required|exists:class_subjects,id',
            'title'            => 'required|string|max:255',
            'description'      => 'required|string|min:5',
            'max_score'        => 'required|numeric|min:1|max:100',
            'deadline_date'    => 'nullable|string',
            'deadline_time'    => 'nullable|string',
            'attachment'       => 'nullable|file|max:5120',
        ], [
            'title.required'       => 'عنوان تمرین الزامی است.',
            'description.required' => 'متن تمرین الزامی است.',
            'max_score.required'   => 'حداکثر نمره را وارد کنید.',
        ]);
    }

    private function combineDateTime(?string $jalaliDate, ?string $time): ?string
    {
        if (!$jalaliDate || !$time) return null;
        $g = to_gregorian($jalaliDate);
        return $g ? $g . ' ' . $time . ':00' : null;
    }

    private function resolveSubmittedAt(Request $request): \Illuminate\Support\Carbon
    {
        $date = $request->input('submitted_date');
        $time = $request->input('submitted_time');

        if ($date) {
            $gregorian = to_gregorian($date);

            if ($gregorian) {
                return \Illuminate\Support\Carbon::parse($gregorian . ' ' . ($time ?: '00:00') . ':00');
            }
        }

        if ($time) {
            return now()->setTimeFromTimeString($time);
        }

        return now();
    }
}
