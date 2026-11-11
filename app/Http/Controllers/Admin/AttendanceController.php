<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Models\ClassSubject;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function destroyDay(ClassSubject $classSubject, AttendanceDay $day)
    {
        abort_unless($day->class_subject_id === $classSubject->id, 404);
        $date = $day->date->format('Y-m-d');
        $day->delete();

        return redirect()->route('admin.attendance.show', [
            'classSubject' => $classSubject->id,
            'date'         => $date,
        ])->with('success', 'روز حذف شد. می‌توانید دوباره شروع کنید.');
    }
    /**
     * نمایش صفحه حضور و غیاب
     * ?date=Y-m-d (پیش‌فرض امروز)
     */
    public function show(Request $request, ClassSubject $classSubject)
    {
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

        // ← جدید
        $pointCategories = \App\Models\PointCategory::active()->orderBy('name')->get();

        $pointEntries = \App\Models\PointEntry::with(['student', 'category', 'createdBy'])
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

    /**
     * شروع یک روز حضور و غیاب
     */
    public function start(Request $request, ClassSubject $classSubject)
    {
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

        return redirect()->route('admin.attendance.show', [
            'classSubject' => $classSubject->id,
            'date'         => $date,
        ])->with('success', 'روز شروع شد.');
    }

    /**
     * ذخیره تغییرات
     */
    public function save(Request $request, ClassSubject $classSubject)
    {
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

        return redirect()->route('admin.attendance.show', [
            'classSubject' => $classSubject->id,
            'date'         => $request->date,
        ])->with('success', 'حضور و غیاب ذخیره شد.');
    }
    public function addPoint(Request $request, ClassSubject $classSubject)
    {
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

        \App\Models\PointEntry::create([
            'student_id'        => $request->student_id,
            'class_subject_id'  => $classSubject->id,
            'point_category_id' => $request->point_category_id,
            'points'            => $request->points,
            'note'              => $request->note,
            'entry_date'        => $request->date,   // از قبل Y-m-d است
            'created_by'        => auth()->id(),
        ]);

        return back()->with('success', 'امتیاز ثبت شد.');
    }

    public function deletePoint(ClassSubject $classSubject, \App\Models\PointEntry $pointEntry)
    {
        abort_unless($pointEntry->class_subject_id === $classSubject->id, 404);
        $pointEntry->delete();
        return back()->with('success', 'امتیاز حذف شد.');
    }
}
