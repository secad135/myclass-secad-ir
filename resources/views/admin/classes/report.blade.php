@php
$isTeacher = auth()->user()->is_teacher;
$isManager = !$isTeacher && auth()->user()->is_school_manager;
$panelPrefix = $isTeacher ? 'teacher' : ($isManager ? 'manager' : 'admin');
$layout = $isTeacher ? 'layouts.teacher' : ($isManager ? 'layouts.manager' : 'layouts.admin');
@endphp
@extends($layout)

@section('title', 'گزارش کلاس ' . $class->name)

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

        .d-flex.justify-content-between .d-flex.gap-2 {
            flex-direction: column;
            width: 100%;
        }

        .d-flex.justify-content-between .d-flex.gap-2 .btn {
            width: 100%;
            min-height: 44px;
            font-size: 0.95rem;
        }

        /* کارت‌های خلاصه زیر هم */
        .row.g-3.mb-3>[class*="col-md-3"] {
            width: 50%;
        }

        .row.g-3.mb-3 .card h3 {
            font-size: 1.4rem;
        }

        /* فیلتر تاریخ */
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

        /* جدول رتبه‌بندی */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }

        .table-responsive table {
            min-width: 720px;
            font-size: 0.82rem;
        }

        .table-responsive table th,
        .table-responsive table td {
            padding: 6px 5px !important;
            white-space: nowrap;
        }

        .table-responsive table td strong {
            font-size: 0.88rem;
            display: block;
        }

        .table-responsive table td small {
            font-size: 0.72rem;
            word-break: break-all;
        }

        /* رتبه */
        .table-responsive table .rank-badge {
            font-size: 0.95rem !important;
        }

        /* بج‌ها */
        .badge {
            font-size: 0.72rem !important;
            padding: 3px 6px;
        }

        /* دکمه وضعیت کلی در جدول */
        .table-responsive table .btn-sm {
            font-size: 0.72rem;
            padding: 4px 8px;
        }

        /* فیلتر بازه */
        .card-body .mt-2.small {
            font-size: 0.78rem;
            line-height: 1.6;
        }
    }
</style>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">گزارش کلاس: {{ $class->name }}</h4>
        <small class="text-muted">
            مدرسه: {{ $class->school?->name }} |
            سال: {{ $class->academicYear?->title }} |
            پایه: {{ $class->grade?->name }} |
            معلم: {{ $class->teacher?->name }}
        </small>
    </div>
    <div class="d-flex gap-2">
        @if($isManager)
        <a href="{{ route('manager.dashboard') }}" class="btn btn-outline-secondary">بازگشت به داشبورد</a>
        @else
        <a href="{{ route($panelPrefix . '.classes.full-report', ['class' => $class, 'quick' => $quickFilter ?? 'all']) }}" class="btn btn-success">📄 گزارش کامل برای چاپ</a>
        <a href="{{ route($panelPrefix . '.classes.show', $class) }}" class="btn btn-outline-secondary">بازگشت</a>
        @endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route($panelPrefix . '.classes.report', $class) }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">از تاریخ (شمسی)</label>
                <input type="text" name="from" class="form-control form-control-sm" value="{{ $quickFilter ? '' : jdate($fromDate) }}" placeholder="1405/07/01" dir="ltr" data-jdp data-jdp-only-date>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">تا تاریخ (شمسی)</label>
                <input type="text" name="to" class="form-control form-control-sm" value="{{ $quickFilter ? '' : jdate($toDate) }}" placeholder="1405/08/09" dir="ltr" data-jdp data-jdp-only-date>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">درس</label>
                <select name="subject" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">همه دروس</option>
                    @foreach($classSubjects as $cs)
                        <option value="{{ $cs->id }}" @selected((int) $selectedSubjectId === $cs->id)>
                            {{ $cs->subject?->name ?? '—' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm">اعمال فیلتر</button>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route($panelPrefix . '.classes.report', ['class' => $class, 'quick' => 'all', 'subject' => $selectedSubjectId]) }}" class="btn btn-outline-secondary {{ ($quickFilter ?? 'all') === 'all' ? 'active' : '' }}">همه (سال تحصیلی)</a>
                    <a href="{{ route($panelPrefix . '.classes.report', ['class' => $class, 'quick' => 'this_month', 'subject' => $selectedSubjectId]) }}" class="btn btn-outline-secondary {{ $quickFilter === 'this_month' ? 'active' : '' }}">این ماه</a>
                    <a href="{{ route($panelPrefix . '.classes.report', ['class' => $class, 'quick' => 'last_30_days', 'subject' => $selectedSubjectId]) }}" class="btn btn-outline-secondary {{ $quickFilter === 'last_30_days' ? 'active' : '' }}">۳۰ روز گذشته</a>
                </div>
            </div>
        </form>
        <div class="mt-2 small text-muted">
            بازه: از <strong>{{ jdate($fromDate) }}</strong> تا <strong>{{ jdate($toDate) }}</strong>
            @if($subjects->count() > 0) | دروس: {{ $subjects->implode('، ') }} @endif
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="small text-muted">تعداد دانش‌آموز</div>
                <h3 class="mb-0">{{ $summary['students_count'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                @if($selectedSubject)
                    @php $selectedSubjectSummary = $subjectSummaries->firstWhere('class_subject_id', $selectedSubjectId); @endphp
                    <div class="small text-muted">میانگین امتیاز درس {{ $selectedSubject->subject?->name ?? '—' }}</div>
                    <h3 class="mb-0 text-{{ ($selectedSubjectSummary['avg_points'] ?? 0) >= 0 ? 'success' : 'danger' }}">{{ ($selectedSubjectSummary['avg_points'] ?? 0) > 0 ? '+' : '' }}{{ $selectedSubjectSummary['avg_points'] ?? 0 }}</h3>
                @else
                    <div class="small text-muted">میانگین جمع امتیاز دروس</div>
                    <h3 class="mb-0 text-{{ $summary['avg_points'] >= 0 ? 'success' : 'danger' }}">{{ $summary['avg_points'] > 0 ? '+' : '' }}{{ $summary['avg_points'] }}</h3>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="small text-muted">میانگین درصد حضور</div>
                <h3 class="mb-0">{{ $summary['avg_percentage'] }}٪</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="small text-muted">جمع غیبت‌ها</div>
                <h3 class="mb-0 text-danger">{{ $summary['total_absences'] }}</h3>
                <small class="text-muted">تاخیر: {{ $summary['total_lates'] }}</small>
            </div>
        </div>
    </div>
</div>

{{-- امتیاز به تفکیک درس --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>امتیاز به تفکیک درس</strong>
        <small class="text-muted">میانگین‌ها بر اساس همه دانش‌آموزان کلاس در بازه انتخابی</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>درس</th>
                    <th class="text-center">میانگین خالص</th>
                    <th class="text-center">میانگین مثبت</th>
                    <th class="text-center">میانگین منفی</th>
                    <th class="text-center">دانش‌آموزان دارای امتیاز</th>
                    <th class="text-center">تعداد ثبت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjectSummaries as $ss)
                    <tr>
                        <td><strong>{{ $ss['subject_name'] }}</strong></td>
                        <td class="text-center">
                            <span class="badge bg-{{ $ss['avg_points'] >= 0 ? 'success' : 'danger' }}">
                                {{ $ss['avg_points'] > 0 ? '+' : '' }}{{ $ss['avg_points'] }}
                            </span>
                        </td>
                        <td class="text-center text-success">+{{ $ss['avg_positive'] }}</td>
                        <td class="text-center text-danger">{{ $ss['avg_negative'] }}</td>
                        <td class="text-center">{{ $ss['students_with_points'] }} / {{ $summary['students_count'] }}</td>
                        <td class="text-center text-muted">{{ $ss['count'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">درسی برای این کلاس تعریف نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <strong>جدول رتبه‌بندی</strong>
        <small class="text-muted">
            @if($selectedSubject)
                مرتب‌شده بر اساس امتیاز درس «{{ $selectedSubject->subject?->name ?? '—' }}»، سپس درصد حضور
            @else
                مرتب‌شده بر اساس جمع امتیاز دروس، سپس درصد حضور
            @endif
        </small>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width: 60px;">رتبه</th>
                    <th>دانش‌آموز</th>
                    @foreach($classSubjects as $cs)
                        <th class="text-center">{{ $cs->subject?->name ?? '—' }}</th>
                    @endforeach
                    <th class="text-center">جمع دروس</th>
                    <th class="text-center">نمره رتبه</th>
                    <th class="text-center">درصد حضور</th>
                    <th class="text-center">غایب</th>
                    <th class="text-center">تاخیر</th>

                    <th class="text-center"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($ranking as $row)
                @php
                $medal = match ($row['rank']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                $pct = $row['percentage'];
                $pctColor = $pct >= 85 ? 'success' : ($pct >= 70 ? 'warning text-dark' : 'danger');
                @endphp
                <tr class="{{ $row['rank'] <= 3 ? 'rank-' . $row['rank'] : '' }}">
                    <td class="text-center fw-bold">{{ $medal }} {{ $row['rank'] }}</td>
                    <td>
                        <strong>{{ $row['student']->name }}</strong>
                        <br><small class="text-muted">{{ $row['student']->email }}</small>
                    </td>
                    @foreach($classSubjects as $cs)
                        @php
                            $subjectPoint = $row['by_subject']->get($cs->id);
                        @endphp
                        <td class="text-center">
                            @if($subjectPoint)
                                <span class="badge bg-{{ $subjectPoint['total'] >= 0 ? 'success' : 'danger' }}" style="font-size: 0.9rem;">
                                    {{ $subjectPoint['total'] > 0 ? '+' : '' }}{{ $subjectPoint['total'] }}
                                </span>
                                @if($subjectPoint['positive'] > 0 || $subjectPoint['negative'] < 0)
                                    <br><small class="text-muted" style="font-size: 0.72rem;">
                                        (<span class="text-success">+{{ $subjectPoint['positive'] }}</span> /
                                        <span class="text-danger">{{ $subjectPoint['negative'] }}</span>)
                                    </small>
                                @endif
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="text-center">
                        <span class="badge bg-{{ $row['points'] >= 0 ? 'success' : 'danger' }}" style="font-size: 0.95rem;">{{ $row['points'] > 0 ? '+' : '' }}{{ $row['points'] }}</span>
                        @if($row['positive_sum'] > 0 || $row['negative_sum'] < 0)
                            <br><small class="text-muted" style="font-size: 0.75rem;">
                                (<span class="text-success">+{{ $row['positive_sum'] }}</span> / <span class="text-danger">{{ $row['negative_sum'] }}</span>)
                            </small>
                        @endif
                    </td>
                    <td class="text-center">
                        @php
                            $rankScore = $row['rank_score'] ?? null;
                            $rankColor = $rankScore === null ? 'secondary' : ($rankScore >= 85 ? 'success' : ($rankScore >= 70 ? 'warning text-dark' : ($rankScore >= 50 ? 'info text-dark' : 'danger')));
                        @endphp
                        @if($rankScore !== null)
                            <span class="badge bg-{{ $rankColor }}" style="font-size: 0.95rem;">{{ number_format($rankScore, 1) }}</span>
                            <br>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1 py-0 px-2" style="font-size: 0.72rem;"
                                data-bs-toggle="modal" data-bs-target="#rankModal{{ $row['student']->id }}">
                                جزئیات
                            </button>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center"><span class="badge bg-{{ $pctColor }}">{{ $pct }}٪</span></td>
                    <td class="text-center">
                        @if($row['stats']['absent'] > 0)
                        <span class="badge bg-danger">{{ $row['stats']['absent'] }}</span>
                        @else
                        <span class="text-muted">{{ $row['stats']['absent'] }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($row['stats']['late'] > 0)
                        <span class="badge bg-warning text-dark">{{ $row['stats']['late'] }}</span>
                        @else
                        <span class="text-muted">{{ $row['stats']['late'] }}</span>
                        @endif
                    </td>
                   
                    <td class="text-center">
                        <a href="{{ route($panelPrefix . '.students.show', ['student' => $row['student'], 'class_id' => $class->id]) }}"
                            class="btn btn-sm btn-outline-info">
                            وضعیت کلی
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 8 + $classSubjects->count() }}" class="text-center text-muted py-4">برای این کلاس در این بازه هیچ داده‌ای وجود ندارد.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($ranking as $row)
    @php $components = $row['rank_components'] ?? []; @endphp
    @if(!empty($components))
    <div class="modal fade" id="rankModal{{ $row['student']->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">ترکیب نمره رتبه — {{ $row['student']->name }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>مؤلفه</th>
                                <th class="text-center">نمره (۰–۱۰۰)</th>
                                <th class="text-center">وزن</th>
                                <th class="text-center">جزئیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>آزمون‌ها</td>
                                <td class="text-center">{{ $components['exams']['score'] !== null ? number_format($components['exams']['score'], 1) : '—' }}</td>
                                <td class="text-center">{{ (int) round($components['exams']['weight'] * 100) }}٪</td>
                                <td class="text-center small text-muted">
                                    {{ $components['exams']['counted'] }} آزمون
                                    @if($components['exams']['missed'] > 0)
                                        · <span class="text-danger">{{ $components['exams']['missed'] }} عدم شرکت</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>تمرین‌ها</td>
                                <td class="text-center">{{ $components['exercises']['score'] !== null ? number_format($components['exercises']['score'], 1) : '—' }}</td>
                                <td class="text-center">{{ (int) round($components['exercises']['weight'] * 100) }}٪</td>
                                <td class="text-center small text-muted">
                                    {{ $components['exercises']['counted'] }} تمرین
                                    @if($components['exercises']['missed'] > 0)
                                        · <span class="text-danger">{{ $components['exercises']['missed'] }} عدم شرکت</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>حضور</td>
                                <td class="text-center">{{ $components['attendance']['score'] !== null ? number_format($components['attendance']['score'], 1) : '—' }}</td>
                                <td class="text-center">{{ (int) round($components['attendance']['weight'] * 100) }}٪</td>
                                <td class="text-center small text-muted">
                                    {{ $components['attendance']['absence_days'] }} روز غیبت از {{ $components['attendance']['held_days'] }} روز
                                </td>
                            </tr>
                            <tr>
                                <td>امتیاز دستی</td>
                                <td class="text-center">{{ $components['manual']['score'] !== null ? number_format($components['manual']['score'], 1) : '—' }}</td>
                                <td class="text-center">{{ (int) round($components['manual']['weight'] * 100) }}٪</td>
                                <td class="text-center small text-muted">{{ $components['manual']['points'] }} امتیاز</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="alert alert-light border mt-3 mb-0 small text-muted">
                        نمره رتبه از میانگین وزنی مؤلفه‌های موجود محاسبه می‌شود؛ اگر مؤلفه‌ای داده‌ای نداشته باشد، وزن آن بین بقیه تقسیم می‌شود.
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach

@endsection
