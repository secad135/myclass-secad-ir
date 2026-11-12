<?php

namespace App\Http\Controllers\Teacher;

use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Models\ClassSubject;
use App\Models\PointEntry;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class AttendanceController extends BaseController
{
    public function show(Request $request, ClassSubject $classSubject)
    {
        $this->assertOwnsClassSubject($classSubject);

        $rawDate = $request->input('date', now()->toDateString());
        $date = to_gregorian($rawDate) ?? now()->toDateString();

        $classSubject->load([
            'subject',
            'schoolClass.students',
            'schoolClass.school',
            'schoolClass.academicYear',
        ]);

        $day = AttendanceDay::where('class_subject_id', $classSubject->id)
            ->where('date', $date)
            ->first();

        $students = $classSubject->schoolClass->students()->orderBy('name')->get();

        $attendances = [];
        if ($day) {
            foreach ($day->attendances as $att) {
                $attendances[$att->student_id][$att->session_number] = $att;
            }
        }

        $sessionsCount = $day?->sessions_count ?? $classSubject->subject->sessions_per_day;

        $recordedDays = AttendanceDay::where('class_subject_id', $classSubject->id)
            ->orderByDesc('date')
            ->get();

        $pointCategories = \App\Models\PointCategory::active()->orderBy('name')->get();

        $pointEntries = PointEntry::with(['student', 'category', 'createdBy'])
            ->where('class_subject_id', $classSubject->id)
            ->where('entry_date', $date)
            ->orderByDesc('id')
            ->get();

        return view('admin.attendance.show', compact(
            'classSubject',
            'date',
            'day',
            'students',
            'attendances',
            'sessionsCount',
            'recordedDays',
            'pointCategories',
            'pointEntries'
        ));
    }

    public function start(Request $request, ClassSubject $classSubject)
    {
        $this->assertOwnsClassSubject($classSubject);

        $request->validate([
            'date'           => 'required|date',
            'sessions_count' => 'required|integer|between:1,8',
            'start_period'   => 'required|integer|between:1,4',
        ]);

        $sessionsCount = (int) $request->sessions_count;
        $startPeriod   = (int) $request->start_period;

        if ($startPeriod + $sessionsCount - 1 > 4) {
            return back()->withErrors([
                'start_period' => 'زنگ شروع + تعداد جلسات نباید از ۴ بیشتر شود.',
            ])->withInput();
        }

        $date = $request->date;

        $day = AttendanceDay::firstOrCreate(
            ['class_subject_id' => $classSubject->id, 'date' => $date],
            [
                'is_started'     => true,
                'started_at'     => now(),
                'sessions_count' => $sessionsCount,
                'start_period'   => $startPeriod,
            ]
        );

        $students = $classSubject->schoolClass->students;

        foreach ($students as $student) {
            for ($s = 1; $s <= $sessionsCount; $s++) {
                Attendance::firstOrCreate(
                    [
                        'attendance_day_id' => $day->id,
                        'student_id'        => $student->id,
                        'session_number'    => $s,
                    ],
                    [
                        'status'        => 'absent',
                        'note'          => null,
                        'is_customized' => false,
                    ]
                );
            }
        }

        ActivityLogger::log(
            auth()->user(),
            'attendance_start',
            'روز حضور و غیاب درس ' . ($classSubject->subject?->name ?? '—') . ' کلاس ' . ($classSubject->schoolClass?->name ?? '—') . ' را شروع کرد.'
        );

        return redirect()->route('teacher.attendance.show', [
            'classSubject' => $classSubject->id,
            'date'         => $date,
        ])->with('success', 'روز شروع شد.');
    }

    public function save(Request $request, ClassSubject $classSubject)
    {
        $this->assertOwnsClassSubject($classSubject);

        $request->validate([
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.*.status' => 'required|in:present,absent,late,excused,left,cancelled',
            'attendances.*.*.note' => 'nullable|string|max:500',
            'attendances.*.*.customized' => 'nullable|in:0,1',
        ]);

        $day = AttendanceDay::where('class_subject_id', $classSubject->id)
            ->where('date', $request->date)
            ->firstOrFail();

        foreach ($request->attendances as $studentId => $sessions) {
            foreach ($sessions as $sessionNum => $data) {
                $customized = $sessionNum > 1 && ($data['customized'] ?? 0) == 1;

                Attendance::updateOrCreate(
                    [
                        'attendance_day_id' => $day->id,
                        'student_id'        => $studentId,
                        'session_number'    => $sessionNum,
                    ],
                    [
                        'status'        => $data['status'],
                        'note'          => $data['note'] ?? null,
                        'is_customized' => $customized,
                    ]
                );
            }
        }

        ActivityLogger::log(
            auth()->user(),
            'attendance_save',
            'حضور و غیاب درس ' . ($classSubject->subject?->name ?? '—') . ' کلاس ' . ($classSubject->schoolClass?->name ?? '—') . ' را ذخیره کرد.'
        );

        return redirect()->route('teacher.attendance.show', [
            'classSubject' => $classSubject->id,
            'date'         => $request->date,
        ])->with('success', 'حضور و غیاب ذخیره شد.');
    }

    public function destroyDay(ClassSubject $classSubject, AttendanceDay $day)
    {
        $this->assertOwnsClassSubject($classSubject);
        abort_unless($day->class_subject_id === $classSubject->id, 404);

        $date = $day->date->format('Y-m-d');
        $day->delete();

        ActivityLogger::log(
            auth()->user(),
            'attendance_delete',
            'روز حضور و غیاب درس ' . ($classSubject->subject?->name ?? '—') . ' کلاس ' . ($classSubject->schoolClass?->name ?? '—') . ' را حذف کرد.'
        );

        return redirect()->route('teacher.attendance.show', [
            'classSubject' => $classSubject->id,
            'date'         => $date,
        ])->with('success', 'روز حذف شد. می‌توانید دوباره شروع کنید.');
    }

    public function addPoint(Request $request, ClassSubject $classSubject)
    {
        $this->assertOwnsClassSubject($classSubject);

        $request->validate([
            'student_id'        => 'required|exists:users,id',
            'point_category_id' => 'nullable|exists:point_categories,id',
            'points'            => 'required|integer|between:-100,100',
            'note'              => 'nullable|string|max:500',
            'date'              => 'required|date',
        ]);

        $isEnrolled = $classSubject->schoolClass->students()
            ->where('users.id', $request->student_id)->exists();
        abort_unless($isEnrolled, 422, 'دانش‌آموز در این کلاس نیست.');

        $entry = PointEntry::create([
            'student_id'        => $request->student_id,
            'class_subject_id'  => $classSubject->id,
            'point_category_id' => $request->point_category_id,
            'points'            => $request->points,
            'note'              => $request->note,
            'entry_date'        => $request->date,
            'created_by'        => auth()->id(),
        ]);

        $entry->loadMissing('student');
        ActivityLogger::log(
            auth()->user(),
            'point_add',
            'برای ' . ($entry->student?->name ?? 'دانش‌آموز') . ' در درس ' . ($classSubject->subject?->name ?? '—') . ' امتیاز ' . $request->points . ' ثبت کرد.'
        );

        return back()->with('success', 'امتیاز ثبت شد.');
    }

    public function deletePoint(ClassSubject $classSubject, PointEntry $pointEntry)
    {
        $this->assertOwnsClassSubject($classSubject);
        abort_unless($pointEntry->class_subject_id === $classSubject->id, 404);

        abort_unless((int) $pointEntry->created_by === (int) auth()->id(), 403);

        $pointEntry->loadMissing('student');
        $pointEntry->delete();

        ActivityLogger::log(
            auth()->user(),
            'point_delete',
            'امتیاز ' . ($pointEntry->student?->name ?? 'دانش‌آموز') . ' در درس ' . ($classSubject->subject?->name ?? '—') . ' را حذف کرد.'
        );

        return back()->with('success', 'امتیاز حذف شد.');
    }
}
