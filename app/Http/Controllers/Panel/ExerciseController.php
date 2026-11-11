<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\ExerciseSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExerciseController extends Controller
{
    public function index()
    {
        $student = auth()->user();

        $classIds = $student->classes()->pluck('school_classes.id')->toArray();

        if (empty($classIds)) {
            return view('panel.exercises.index', ['exercises' => collect(), 'submissions' => collect()]);
        }

        $exercises = Exercise::with(['classSubject.subject', 'classSubject.schoolClass'])
            ->whereHas('classSubject', fn($q) => $q->whereIn('school_class_id', $classIds))
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->orderByDesc('deadline_at')
            ->get();

        $submissions = ExerciseSubmission::where('student_id', $student->id)
            ->get()
            ->keyBy('exercise_id');

        return view('panel.exercises.index', compact('exercises', 'submissions'));
    }

    public function show(Exercise $exercise)
    {
        $student = auth()->user();

        $classIds = $student->classes()->pluck('school_classes.id')->toArray();
        abort_unless(in_array($exercise->class_subject_id, Exercise::where('id', $exercise->id)
            ->whereHas('classSubject', fn($q) => $q->whereIn('school_class_id', $classIds))
            ->pluck('class_subject_id')->toArray()), 403);

        $exercise->load(['classSubject.subject']);

        $submission = $exercise->submissions()
            ->where('student_id', $student->id)
            ->first();

        return view('panel.exercises.show', compact('exercise', 'submission'));
    }

    public function submit(Request $request, Exercise $exercise)
    {
        $student = auth()->user();

        if (!$exercise->isAcceptingSubmissions()) {
            return back()->with('error', 'این تمرین در حال حاضر پاسخ نمی‌پذیرد.');
        }

        $request->validate([
            'content'      => 'nullable|string|max:10000',
            'code_content' => 'nullable|string|max:50000',
            'file'         => 'nullable|file|max:10240',
        ]);

        // اعتبارسنجی شرطی
        if ($exercise->requires_code && !$request->filled('code_content')) {
            return back()->with('error', 'این تمرین کدنویسی است. لطفاً کد خود را وارد کنید.')->withInput();
        }

        if (!$exercise->requires_code && !$request->filled('content') && !$request->hasFile('file')) {
            return back()->with('error', 'حداقل متن پاسخ یا فایل را وارد کنید.');
        }

        if ($request->hasFile('file') && !$exercise->allow_file_upload) {
            return back()->with('error', 'آپلود فایل برای این تمرین مجاز نیست.');
        }

        $existing = $exercise->submissions()->where('student_id', $student->id)->first();

        if ($existing && $existing->isGraded()) {
            return back()->with('error', 'پاسخ شما تصحیح شده و قابل تغییر نیست.');
        }

        $filePath = $existing?->file_path;
        if ($request->hasFile('file')) {
            if ($filePath) Storage::disk('public')->delete($filePath);
            $filePath = $request->file('file')->store('exercise-submissions', 'public');
        }

        $isLate = $exercise->isExpired();

        $exercise->submissions()->updateOrCreate(
            ['student_id' => $student->id],
            [
                'content'      => $request->content,
                'code_content' => $exercise->requires_code ? $request->code_content : null,
                'file_path'    => $filePath,
                'submitted_at' => now(),
                'is_late'      => $isLate,
            ]
        );

        return redirect()->route('panel.exercises.show', $exercise)
            ->with('success', 'پاسخ شما با موفقیت ثبت شد.');
    }
}
