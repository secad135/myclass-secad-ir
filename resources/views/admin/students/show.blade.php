@php
$isManager = auth()->user()->is_school_manager;
$layout = $isManager ? 'layouts.manager' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', 'وضعیت کلی ' . $student->name)

@section('head')
<style>
    @media (max-width: 991.98px) {

        /* کارت‌های خلاصه زیر هم */
        .card-body .row.g-3>[class*="col-"] {
            width: 100%;
            margin-bottom: 8px;
        }

        .card-body .row.g-3>[class*="col-"]:last-child {
            margin-bottom: 0;
        }

        .card-body .row.g-3 h3 {
            font-size: 1.5rem;
        }

        /* هدر */
        .d-flex.justify-content-between h4 {
            font-size: 1.05rem;
        }

        .d-flex.justify-content-between h5 {
            font-size: 0.88rem;
            word-break: break-word;
        }

        /* همه جدول‌ها اسکرول افقی */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }

        .table-responsive table {
            font-size: 0.82rem;
            min-width: 500px;
        }

        .table-responsive table th,
        .table-responsive table td {
            padding: 6px 5px !important;
            white-space: nowrap;
        }

        .table-responsive table td.small,
        .table-responsive table td .small {
            font-size: 0.75rem;
        }

        /* بج‌ها کوچک‌تر */
        .badge {
            font-size: 0.72rem !important;
            padding: 3px 6px;
        }

        /* خلاصه حضور: اسکرول */
        .card-body>.row.text-center.g-2 {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            flex-wrap: nowrap;
            padding-bottom: 8px;
        }

        .card-body>.row.text-center.g-2>.col {
            min-width: 85px;
            flex: 0 0 auto;
        }

        /* فیلتر تاریخ */
        .card-body form.row.g-2 {
            gap: 8px;
        }

        .card-body form.row.g-2>[class*="col-"] {
            width: 100%;
        }

        .btn-group {
            flex-wrap: wrap;
            width: 100%;
        }

        .btn-group .btn {
            flex: 1 1 calc(50% - 4px);
            min-width: 0;
            font-size: 0.82rem;
            padding: 8px 6px;
        }

        /* کارت‌های اطلاعات */
        .card-header {
            padding: 10px 12px;
            font-size: 0.88rem;
        }

        .card-header .d-flex {
            flex-wrap: wrap;
            gap: 6px;
        }

        .card-body {
            padding: 12px 10px !important;
        }

        /* یادداشت‌ها (توضیح زیر جدول) */
        .small.text-muted.d-block.mb-2 {
            font-size: 0.78rem;
            line-height: 1.6;
        }

        /* پنل ذخیره‌شده‌های attendance (اگر بود) */
        .recorded-days-table-wrap {
            max-height: 320px;
            overflow: auto;
        }

        /* دکمه بازگشت */
        .d-flex.justify-content-between .btn {
            font-size: 0.85rem;
            padding: 6px 12px;
        }
    }
</style>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">وضعیت کلی دانش‌آموز</h4>
        <h5 class="text-muted mb-0">{{ $student->name }} — {{ $student->email }}</h5>
    </div>
    @php
        $backRoute = route('manager.dashboard');
        $backText = 'بازگشت به داشبورد';
        $classId = request('class_id');

        if ($classId) {
            $classModel = \App\Models\SchoolClass::find($classId);
            if ($classModel) {
                $backRoute = auth()->user()->is_school_manager 
                    ? route('manager.classes.report', $classModel) 
                    : route('admin.classes.report', $classModel);
                $backText = 'بازگشت به گزارش کلاس';
            }
        } elseif (!auth()->user()->is_school_manager) {
            $backRoute = route('admin.students.index');
            $backText = 'بازگشت به لیست دانش‌آموزان';
        }
    @endphp
    <a href="{{ $backRoute }}" class="btn btn-secondary">{{ $backText }}</a>
        @php
            $classIdForPrint = request('class_id') ?? $student->classes()->first()?->id;
            $printRoute = auth()->user()->is_school_manager 
                ? route('manager.classes.full-report', ['class' => $classIdForPrint, 'student_id' => $student->id])
                : route('admin.classes.full-report', ['class' => $classIdForPrint, 'student_id' => $student->id]);
        @endphp
        @if($classIdForPrint)
            <a href="{{ $printRoute }}" class="btn btn-outline-success me-2" target="_blank">
                🖨️ چاپ کارنامه
            </a>
        @endif
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $isManager ? route('manager.students.show', $student) : route('admin.students.show', $student) }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">از تاریخ (شمسی)</label>
                <input type="text" name="from" class="form-control form-control-sm" value="{{ $quickFilter ? '' : jdate($fromDate) }}" placeholder="1405/07/01" dir="ltr" data-jdp data-jdp-only-date>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">تا تاریخ (شمسی)</label>
                <input type="text" name="to" class="form-control form-control-sm" value="{{ $quickFilter ? '' : jdate($toDate) }}" placeholder="1405/08/09" dir="ltr" data-jdp data-jdp-only-date>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm">اعمال فیلتر</button>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route($isManager ? 'manager.students.show' : 'admin.students.show', ['student' => $student, 'quick' => 'all']) }}" class="btn btn-outline-secondary {{ ($quickFilter ?? 'all') === 'all' ? 'active' : '' }}">همه (سال تحصیلی)</a>
                    <a href="{{ route($isManager ? 'manager.students.show' : 'admin.students.show', ['student' => $student, 'quick' => 'this_month']) }}" class="btn btn-outline-secondary {{ $quickFilter === 'this_month' ? 'active' : '' }}">این ماه</a>
                    <a href="{{ route($isManager ? 'manager.students.show' : 'admin.students.show', ['student' => $student, 'quick' => 'last_30_days']) }}" class="btn btn-outline-secondary {{ $quickFilter === 'last_30_days' ? 'active' : '' }}">۳۰ روز گذشته</a>
                </div>
            </div>
        </form>
        <div class="mt-2 small text-muted">
            بازه فعلی: از <strong>{{ jdate($fromDate) }}</strong> تا <strong>{{ jdate($toDate) }}</strong> (سال تحصیلی: {{ $currentYear->title }})
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card text-center border-{{ $points['total'] >= 0 ? 'success' : 'danger' }}">
            <div class="card-body">
                <div class="small text-muted">جمع امتیاز دروس</div>
                <h3 class="mb-0 text-{{ $points['total'] >= 0 ? 'success' : 'danger' }}">{{ $points['total'] > 0 ? '+' : '' }}{{ $points['total'] }}</h3>
                <small class="text-muted">(+{{ $points['positive'] }} / {{ $points['negative'] }})</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="small text-muted">درصد حضور</div>
                <h3 class="mb-0 text-{{ $status['color'] }}">{{ $overall['percentage'] }}٪</h3>
                <span class="badge bg-{{ $status['color'] }}">{{ $status['label'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="small text-muted">غیبت</div>
                <h3 class="mb-0 text-danger">{{ $overall['absent'] }}</h3>
                <small class="text-muted">جلسه</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="small text-muted">تاخیر</div>
                <h3 class="mb-0" style="color:#b45309;">{{ $overall['late'] }}</h3>
                <small class="text-muted">جلسه</small>
            </div>
        </div>
    </div>
</div>

{{-- امتیاز به تفکیک درس --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>امتیاز به تفکیک درس</strong>
        <small class="text-muted">در بازه انتخاب‌شده — جمع هر درس جداگانه محاسبه می‌شود</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>درس</th>
                    <th>کلاس</th>
                    <th class="text-center">امتیاز خالص</th>
                    <th class="text-center">مثبت</th>
                    <th class="text-center">منفی</th>
                    <th class="text-center">تعداد ثبت</th>
                    <th class="text-center">آخرین ثبت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($points['by_subject'] as $subjectPoint)
                    <tr>
                        <td><strong>{{ $subjectPoint['subject_name'] }}</strong></td>
                        <td><small class="text-muted">{{ $subjectPoint['class_name'] }}</small></td>
                        <td class="text-center">
                            <span class="badge bg-{{ $subjectPoint['total'] >= 0 ? 'success' : 'danger' }}">
                                {{ $subjectPoint['total'] > 0 ? '+' : '' }}{{ $subjectPoint['total'] }}
                            </span>
                        </td>
                        <td class="text-center text-success">+{{ $subjectPoint['positive'] }}</td>
                        <td class="text-center text-danger">{{ $subjectPoint['negative'] }}</td>
                        <td class="text-center text-muted">{{ $subjectPoint['count'] }}</td>
                        <td class="text-center small text-muted">
                            {{ $subjectPoint['last_entry_date'] ? jdate($subjectPoint['last_entry_date']) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">درسی برای این دانش‌آموز یافت نشد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- نتایج آزمون‌ها --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>نتایج آزمون‌ها ({{ $examResults->count() }} آزمون)</strong>
        <small class="text-muted">
            همه آزمون‌های برگزارشده (مستقل از فیلتر تاریخ)
        </small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>آزمون</th>
                    <th>درس</th>
                    <th class="text-center">زمان برگزاری</th>
                    <th class="text-center">بهترین نمره</th>
                    <th class="text-center">تلاش‌ها</th>
                    <th class="text-center">وضعیت</th>
                    <th class="text-center"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($examResults as $row)
                @php
                $exam = $row['exam'];
                $best = $row['best_attempt'];
                $isComplete = $row['attempts_count'] >= $exam->max_attempts;
                @endphp
                <tr>
                    <td><strong>{{ $exam->title }}</strong></td>
                    <td><small>{{ $exam->classSubject?->subject?->name ?? '—' }}</small></td>
                    <td class="text-center small">
                        @if($exam->start_at)
                        {{ jdate($exam->start_at, 'yyyy/MM/dd') }}
                        @else
                        —
                        @endif
                    </td>
                    <td class="text-center">
                        @if($best)
                        <span class="badge bg-{{ $best->final_score >= 17 ? 'success' : ($best->final_score >= 10 ? 'warning text-dark' : 'danger') }}" style="font-size: 0.95rem;">
                            {{ $best->final_score }} / 20
                        </span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $row['attempts_count'] }} / {{ $exam->max_attempts }}
                    </td>
                    <td class="text-center">
                        @if(!$row['participated'])
                        <span class="badge bg-danger">شرکت نکرده</span>
                        @elseif($isComplete)
                        <span class="badge bg-success">تکمیل شده</span>
                        @else
                        <span class="badge bg-warning text-dark">فرصت باقی</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($best && !$isManager)
                        <a href="{{ route('admin.exams.attempt-detail', [$exam, $best]) }}"
                            class="btn btn-sm btn-outline-info">مشاهده</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        هنوز هیچ آزمونی برای این دانش‌آموز برگزار نشده است.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- نتایج تمرین‌ها --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>نتایج تمرین‌ها ({{ $exerciseResults->count() }} تمرین)</strong>
        <small class="text-muted">
            همه تمرین‌های در دسترس (مستقل از فیلتر تاریخ)
        </small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>تمرین</th>
                    <th>درس</th>
                    <th class="text-center">مهلت</th>
                    <th class="text-center">نمره</th>
                    <th class="text-center">تاریخ ارسال</th>
                    <th class="text-center">وضعیت</th>
                    <th class="text-center"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($exerciseResults as $row)
                @php
                $exercise = $row['exercise'];
                $sub = $row['submission'];
                @endphp
                <tr>
                    <td><strong>{{ $exercise->title }}</strong></td>
                    <td><small>{{ $exercise->classSubject?->subject?->name ?? '—' }}</small></td>
                    <td class="text-center small">
                        @if($exercise->deadline_at)
                        {{ jdate($exercise->deadline_at, 'yyyy/MM/dd') }}
                        @else
                        —
                        @endif
                    </td>
                    <td class="text-center">
                        @if($row['is_graded'])
                        @php
                        $pct = $row['max_score'] > 0 ? ($row['score'] / $row['max_score']) * 100 : 0;
                        $color = $pct >= 85 ? 'success' : ($pct >= 50 ? 'warning text-dark' : 'danger');
                        @endphp
                        <span class="badge bg-{{ $color }}" style="font-size: 0.95rem;">
                            {{ $row['score'] }} / {{ $row['max_score'] }}
                        </span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center small">
                        @if($row['submitted_at'])
                        {{ jdate($row['submitted_at'], 'yyyy/MM/dd') }}
                        @else
                        —
                        @endif
                    </td>
                    <td class="text-center">
                        @if(!$row['submitted'])
                        <span class="badge bg-secondary">ارسال نشده</span>
                        @elseif($row['is_graded'])
                        <span class="badge bg-success">تصحیح شده</span>
                        @else
                        <span class="badge bg-warning text-dark">در انتظار تصحیح</span>
                        @if($row['is_late'])
                        <span class="badge bg-danger">تأخیر</span>
                        @endif
                        @endif
                    </td>
                    <td class="text-center">
                        @if($sub && !$isManager)
                        <a href="{{ route('admin.exercises.submissions', $exercise) }}"
                            class="btn btn-sm btn-outline-info">مشاهده</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        هنوز هیچ تمرینی برای این دانش‌آموز وجود ندارد.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>خلاصه حضور و غیاب</strong></div>
    <div class="card-body">
        <div class="row text-center g-2">
            <div class="col"><span class="badge bg-success">حاضر</span><br><strong>{{ $overall['present'] }}</strong></div>
            <div class="col"><span class="badge bg-warning text-dark">تاخیر</span><br><strong>{{ $overall['late'] }}</strong></div>
            <div class="col"><span class="badge bg-info">با اجازه</span><br><strong>{{ $overall['excused'] }}</strong></div>
            <div class="col"><span class="badge bg-danger">غایب</span><br><strong>{{ $overall['absent'] }}</strong></div>
            <div class="col"><span class="badge bg-secondary">ترک کلاس</span><br><strong>{{ $overall['left'] }}</strong></div>
            <div class="col"><span class="badge bg-secondary">برگزار نشد</span><br><strong>{{ $overall['cancelled'] }}</strong></div>
            <div class="col border-start"><span class="text-muted small">کل برگزار شده</span><br><strong>{{ $overall['total_held'] }}</strong></div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>آمار به تفکیک درس</strong></div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>درس</th>
                    <th>کلاس / مدرسه</th>
                    <th class="text-center">حاضر</th>
                    <th class="text-center">تاخیر</th>
                    <th class="text-center">اجازه</th>
                    <th class="text-center">غایب</th>
                    <th class="text-center">ترک</th>
                    <th class="text-center">برگزار نشد</th>
                    <th class="text-center">درصد</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bySubject as $s)
                <tr>
                    <td>{{ $s['subject_name'] }}</td>
                    <td><small>{{ $s['class_name'] }} / {{ $s['school_name'] }}</small></td>
                    <td class="text-center">{{ $s['present'] }}</td>
                    <td class="text-center">{{ $s['late'] }}</td>
                    <td class="text-center">{{ $s['excused'] }}</td>
                    <td class="text-center text-danger">{{ $s['absent'] }}</td>
                    <td class="text-center">{{ $s['left'] }}</td>
                    <td class="text-center text-muted">{{ $s['cancelled'] }}</td>
                    <td class="text-center">
                        <span class="badge bg-{{ $s['percentage'] >= 85 ? 'success' : ($s['percentage'] >= 70 ? 'warning text-dark' : 'danger') }}">{{ $s['percentage'] }}٪</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted">هیچ درسی یافت نشد.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between">
        <strong>امتیازات دستی ({{ $points['count'] }} مورد)</strong>
        <span>
            <span class="badge bg-success">مثبت: +{{ $points['positive'] }}</span>
            <span class="badge bg-danger">منفی: {{ $points['negative'] }}</span>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>تاریخ</th>
                    <th>درس</th>
                    <th>دسته</th>
                    <th class="text-center">امتیاز</th>
                    <th>توضیح</th>
                    <th>ثبت‌کننده</th>
                </tr>
            </thead>
            <tbody>
                @forelse($points['entries'] as $entry)
                <tr>
                    <td>{{ jdate($entry->entry_date) }}</td>
                    <td><small>{{ $entry->classSubject?->subject?->name ?? '—' }}</small></td>
                    <td>
                        @if($entry->category)
                        <span class="badge bg-{{ $entry->category->color() }}">{{ $entry->category->name }}</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-{{ $entry->points >= 0 ? 'success' : 'danger' }}">{{ $entry->points > 0 ? '+' : '' }}{{ $entry->points }}</span>
                    </td>
                    <td><small>{{ $entry->note }}</small></td>
                    <td><small class="text-muted">{{ $entry->createdBy?->name }}</small></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">امتیازی ثبت نشده است.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>



{{-- ریز حضور و غیاب (جدول روز-محور به تفکیک درس) --}}
@if($groupedAttendance->count() > 0)
<div class="mt-3 mb-2">
    <h5 class="mb-2">ریز حضور و غیاب (به تفکیک درس)</h5>
    <div class="small text-muted mb-3" style="line-height: 1.9;">
        <strong>راهنما:</strong>
        <span class="me-2">✅ حاضر</span>
        <span class="me-2">❌ غایب</span>
        <span class="me-2">⏰ تاخیر</span>
        <span class="me-2">📝 با اجازه</span>
        <span class="me-2">🚪 ترک کلاس</span>
        <span class="me-2">🚫 برگزار نشد</span>
        <span class="me-2">➖ ثبت نشده</span>
    </div>
</div>

@foreach($groupedAttendance as $item)
@php
$cs = $item['class_subject'];
$rows = $item['rows'];
$dayCount = $rows->count();
@endphp
<div class="card mb-3">
    <div class="card-header">
        <strong>{{ $cs->subject?->name ?? '—' }}</strong>
        <small class="text-muted">
            — {{ $cs->schoolClass?->name }}
            @if($cs->schoolClass?->school?->name)
            / {{ $cs->schoolClass->school->name }}
            @endif
            | {{ $dayCount }} روز
        </small>
    </div>
    <div class="card-body">
        <div style="max-width: 75%;">
            @include('admin.classes.partials.attendance-table', ['rows' => $rows])
        </div>
    </div>
</div>
@endforeach
@else
<div class="card mb-3">
    <div class="card-header"><strong>ریز حضور و غیاب</strong></div>
    <div class="card-body">
        <p class="text-muted small mb-0">در این بازه هیچ جلسه‌ای ثبت نشده است.</p>
    </div>
</div>
@endif

@endsection
