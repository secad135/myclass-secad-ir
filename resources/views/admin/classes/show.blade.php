@php
$isTeacher = auth()->user()->is_teacher;
$panelPrefix = $isTeacher ? 'teacher' : 'admin';
$layout = $isTeacher ? 'layouts.teacher' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', 'کلاس ' . $class->name)

@section('head')
<style>
    @media (max-width: 991.98px) {
        /* هدر */
        .d-flex.justify-content-between.mb-3 {
            flex-direction: column;
            gap: 10px;
            align-items: stretch !important;
        }
        .d-flex.justify-content-between h4 {
            font-size: 1.05rem;
        }
        .d-flex.justify-content-between .small {
            font-size: 0.8rem;
            line-height: 1.6;
        }
        .d-flex.justify-content-between .d-flex.gap-2 {
            flex-direction: column;
            width: 100%;
        }
        .d-flex.justify-content-between .d-flex.gap-2 .btn {
            width: 100%;
            min-height: 44px;
        }

        /* h5 */
        h5 {
            font-size: 1rem;
            margin-top: 1.2rem !important;
        }

        /* جدول‌ها اسکرول افقی */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
        .table-bordered {
            font-size: 0.82rem;
            min-width: 550px;
        }
        .table-bordered th,
        .table-bordered td {
            padding: 6px 5px !important;
        }
        .table-bordered td small {
            font-size: 0.72rem;
            word-break: break-all;
        }

        /* فرم افزودن دانش‌آموز و درس */
        form.d-flex.gap-2 {
            flex-direction: column;
            gap: 8px !important;
        }
        form.d-flex.gap-2 .form-select,
        form.d-flex.gap-2 .form-control,
        form.d-flex.gap-2 .btn {
            width: 100%;
            min-height: 44px;
            font-size: 1rem;
            max-width: none !important;
        }

        /* details ویرایش روزها */
        details summary {
            font-size: 0.82rem;
            padding: 4px 0;
        }
        details form .form-check {
            margin-bottom: 6px;
        }
        details form .form-check-label {
            font-size: 0.88rem;
        }
        details form .btn {
            width: 100%;
            min-height: 40px;
            margin-top: 8px;
        }

        /* دکمه‌های کوچک در جدول */
        .btn-sm {
            font-size: 0.76rem;
            padding: 4px 8px;
        }

        /* بج‌ها */
        .badge { font-size: 0.72rem !important; }

        /* alert */
        .alert {
            font-size: 0.88rem;
            padding: 10px 12px;
        }
    }
</style>
@endsection

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">کلاس {{ $class->name }}</h4>
            <small class="text-muted">
                مدرسه: {{ $class->school?->name }} |
                سال: {{ $class->academicYear?->title }} |
                پایه: {{ $class->grade?->name }} |
                معلم: {{ $class->teacher?->name }}
            </small>
        </div>
        <div class="d-flex gap-2">
            @if(Route::has($panelPrefix . '.classes.report'))
                <a href="{{ route($panelPrefix . '.classes.report', $class) }}" class="btn btn-primary">📊 گزارش کلاس</a>
            @endif
            <a href="{{ route($panelPrefix . '.classes.index') }}" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>

    <h5 class="mt-4">دانش‌آموزان کلاس</h5>

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>ایمیل</th>
                <th>وضعیت</th>
                @if(!$isTeacher)
                    <th>عملیات</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($class->students as $student)
                <tr>
                    <td>{{ $student->id }}</td>
                    <td>
                        {{ $student->name }}
                        <a href="{{ route($panelPrefix . '.students.show', $student) }}" class="text-decoration-none small" title="مشاهده وضعیت">📋</a>
                    </td>
                    <td dir="ltr"><small>{{ $student->email }}</small></td>
                    <td>{{ $student->pivot->status }}</td>
                    @if(!$isTeacher)
                        <td>
                            <form action="{{ route('admin.classes.unenroll', [$class, $student]) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟')">حذف از کلاس</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $isTeacher ? 4 : 5 }}" class="text-center text-muted">هیچ دانش‌آموزی در این کلاس نیست.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if(!$isTeacher)
        <h5 class="mt-4">افزودن دانش‌آموز به کلاس</h5>
        <form action="{{ route('admin.classes.enroll', $class) }}" method="POST" class="d-flex gap-2 mb-4">
            @csrf
            <select name="student_id" class="form-select">
                <option value="">— انتخاب دانش‌آموز —</option>
                @foreach($availableStudents as $student)
                    <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                @endforeach
            </select>
            <button class="btn btn-primary">افزودن</button>
        </form>
    @endif

    <hr class="my-4">

    <h5>دروس این کلاس</h5>

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام درس</th>
                <th>نوع</th>
                <th style="min-width: 190px;">معلم</th>
                <th>جلسه در روز</th>
                <th style="min-width: 280px;">روزهای هفته</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($class->classSubjects as $cs)
                @php
                    $currentDays = $cs->days_of_week ?? $cs->subject?->default_days ?? [];
                @endphp
                <tr>
                    <td>{{ $cs->id }}</td>
                    <td>{{ $cs->subject?->name }}</td>
                    <td>
                        @if($cs->subject?->type === 'workshop')
                            <span class="badge bg-info">کارگاه</span>
                        @else
                            <span class="badge bg-secondary">عادی</span>
                        @endif
                    </td>
                    <td>
                        @if($isTeacher)
                            {{ $cs->teacher?->name ?? '—' }}
                        @else
                            <form action="{{ route('admin.classes.subjects.teacher.update', [$class, $cs]) }}" method="POST" class="d-flex gap-1">
                                @csrf
                                @method('PUT')
                                <select name="teacher_id" class="form-select form-select-sm">
                                    <option value="">— بدون معلم —</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" @selected($cs->teacher_id == $teacher->id)>{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-primary" title="ثبت معلم">ثبت</button>
                            </form>
                        @endif
                    </td>
                    <td>{{ $cs->subject?->sessions_per_day }}</td>
                    <td>
                        <div class="mb-1">
                            @if(!empty($currentDays))
                                @foreach($currentDays as $d)
                                    <span class="badge bg-primary">{{ \App\Models\Subject::dayNames()[$d] ?? '?' }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        <small class="d-block text-muted mt-1">
                            شروع از زنگ {{ $cs->default_start_period ?? 1 }}
                        </small>
                        @if(!$isTeacher)
                        <details>
                            <summary class="small text-primary" style="cursor:pointer;">✏️ ویرایش روزها و زنگ</summary>
                            <form action="{{ route('admin.classes.subjects.days.update', [$class, $cs]) }}" method="POST" class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                                @csrf
                                @method('PUT')
                                @foreach(\App\Models\Subject::dayNames() as $val => $label)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="days_of_week[]" value="{{ $val }}" id="cs{{ $cs->id }}_day{{ $val }}" {{ in_array($val, (array) $currentDays) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="cs{{ $cs->id }}_day{{ $val }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                                <div class="w-100 mt-2">
                                    <label class="form-label small mb-0">زنگ شروع:</label>
                                    <select name="default_start_period" class="form-select form-select-sm" style="max-width: 160px;">
                                        @for($i = 1; $i <= 4; $i++)
                                            <option value="{{ $i }}" @selected($i === ($cs->default_start_period ?? 1))>زنگ {{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <button class="btn btn-sm btn-outline-primary">ذخیره</button>
                            </form>
                        </details>
                        @endif
                    </td>
                    <td>
                        @if(!$isTeacher || (int) $cs->teacher_id === (int) auth()->id())
                            @if(Route::has($panelPrefix . '.attendance.show'))
                                <a href="{{ route($panelPrefix . '.attendance.show', $cs) }}" class="btn btn-sm btn-success">حضور و غیاب</a>
                            @endif
                        @endif
                        @if(!$isTeacher)
                            <form action="{{ route('admin.classes.subjects.remove', [$class, $cs]) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟')">حذف</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">هیچ درسی به این کلاس اضافه نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if(!$isTeacher)
        <h5 class="mt-4">افزودن درس به کلاس</h5>

        @if($availableSubjects->isEmpty())
            <div class="alert alert-info">
                همه درس‌های موجود به این کلاس اضافه شده‌اند یا هنوز درسی تعریف نکرده‌اید.
                <a href="{{ route('admin.subjects.create') }}">افزودن درس جدید</a>
            </div>
        @else
            <form action="{{ route('admin.classes.subjects.add', $class) }}" method="POST" class="d-flex gap-2 mb-4">
                @csrf
                <select name="subject_id" class="form-select">
                    <option value="">— انتخاب درس —</option>
                    @foreach($availableSubjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }} ({{ $subject->typeLabel() }} - {{ $subject->sessions_per_day }} جلسه)</option>
                    @endforeach
                </select>
                <select name="default_start_period" class="form-select" style="max-width: 160px;">
                    @for($i = 1; $i <= 4; $i++)
                        <option value="{{ $i }}" @selected($i === 1)>شروع از زنگ {{ $i }}</option>
                    @endfor
                </select>
                <select name="teacher_id" class="form-select" style="max-width: 220px;">
                    <option value="">— معلم (اختیاری) —</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary">افزودن</button>
            </form>
        @endif
    @endif

@endsection
