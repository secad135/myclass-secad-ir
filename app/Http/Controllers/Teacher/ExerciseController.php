<?php

namespace App\Http\Controllers\Teacher;

use App\Exceptions\UploadRejectedException;
use App\Models\ClassSubject;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use App\Services\ActivityLogger;
use App\Services\Uploads\UploadService;
use Illuminate\Http\Request;

class ExerciseController extends BaseController
{
    public function __construct(private readonly UploadService $uploads)
    {
    }

    public function index()
    {
        $classSubjectIds = $this->ownClassSubjectIds();

        $exercises = Exercise::with(['classSubject.subject', 'classSubject.schoolClass'])
            ->whereIn('class_subject_id', $classSubjectIds)
            ->withCount('submissions')
            ->latest()
            ->paginate(15);

        return view('admin.exercises.index', compact('exercises'));
    }

    public function create()
    {
        $classSubjects = $this->ownClassSubjectsForForm();

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
            'class_subject_id'      => $data['class_subject_id'],
            'created_by'            => auth()->id(),
            'title'                 => $data['title'],
            'description'           => $data['description'],
            'attachment_path'       => $attachmentPath,
            'deadline_at'           => $this->combineDateTime($request->deadline_date, $request->deadline_time),
            'max_score'             => $data['max_score'],
            'allow_late_submission' => $request->boolean('allow_late_submission'),
            'is_active'             => $request->boolean('is_active', true),
            'requires_code'         => $request->boolean('requires_code'),
            'allow_file_upload'     => $request->boolean('allow_file_upload'),
        ]);

        ActivityLogger::log(auth()->user(), 'exercise_create', 'تمرین «' . $data['title'] . '» را ساخت.');

        return redirect()->route('teacher.exercises.index')
            ->with('success', 'تمرین ساخته شد.');
    }

    public function edit(Exercise $exercise)
    {
        $this->assertOwnsExercise($exercise);

        $classSubjects = $this->ownClassSubjectsForForm();

        return view('admin.exercises.edit', compact('exercise', 'classSubjects'));
    }

    public function update(Request $request, Exercise $exercise)
    {
        $this->assertOwnsExercise($exercise);

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
            'class_subject_id'      => $data['class_subject_id'],
            'title'                 => $data['title'],
            'description'           => $data['description'],
            'attachment_path'       => $attachmentPath,
            'deadline_at'           => $this->combineDateTime($request->deadline_date, $request->deadline_time),
            'max_score'             => $data['max_score'],
            'allow_late_submission' => $request->boolean('allow_late_submission'),
            'is_active'             => $request->boolean('is_active'),
            'requires_code'         => $request->boolean('requires_code'),
            'allow_file_upload'     => $request->boolean('allow_file_upload'),
        ]);

        ActivityLogger::log(auth()->user(), 'exercise_update', 'تمرین «' . $data['title'] . '» را ویرایش کرد.');

        return redirect()->route('teacher.exercises.index')
            ->with('success', 'تمرین ویرایش شد.');
    }

    public function destroy(Exercise $exercise)
    {
        $this->assertOwnsExercise($exercise);

        $this->uploads->delete($exercise->attachment_path);
        $title = $exercise->title;
        $exercise->delete();

        ActivityLogger::log(auth()->user(), 'exercise_delete', 'تمرین «' . $title . '» را حذف کرد.');

        return redirect()->route('teacher.exercises.index')
            ->with('success', 'تمرین حذف شد.');
    }

    public function submissions(Exercise $exercise)
    {
        $this->assertOwnsExercise($exercise);

        $exercise->load(['classSubject.subject', 'classSubject.schoolClass']);

        $students = $exercise->classSubject->schoolClass->students()->orderBy('name')->get();
        $submissions = $exercise->submissions()->get()->keyBy('student_id');

        return view('admin.exercises.submissions', compact('exercise', 'students', 'submissions'));
    }

    public function grade(Request $request, Exercise $exercise, ExerciseSubmission $submission)
    {
        $this->assertOwnsExercise($exercise);
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

        $submission->loadMissing('student');
        ActivityLogger::log(
            auth()->user(),
            'exercise_grade',
            'پاسخ ' . ($submission->student?->name ?? 'دانش‌آموز') . ' در تمرین «' . $exercise->title . '» را تصحیح کرد.'
        );

        return redirect()->route('teacher.exercises.submissions', $exercise)
            ->with('success', 'نمره ثبت شد.');
    }

    private function ownClassSubjectsForForm()
    {
        return ClassSubject::with(['subject', 'schoolClass'])
            ->where('teacher_id', $this->teacherId())
            ->whereHas('subject')
            ->get();
    }

    private function validateExercise(Request $request): array
    {
        $data = $request->validate([
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

        $owns = ClassSubject::where('teacher_id', $this->teacherId())
            ->whereKey($data['class_subject_id'])
            ->exists();

        abort_unless($owns, 403);

        return $data;
    }

    private function combineDateTime(?string $jalaliDate, ?string $time): ?string
    {
        if (!$jalaliDate || !$time) {
            return null;
        }

        $g = to_gregorian($jalaliDate);

        return $g ? $g . ' ' . $time . ':00' : null;
    }
}
