<!DOCTYPE html>
<html dir="rtl" lang="fa">

<head>
    <meta charset="UTF-8">
    <title>گزارش کامل — {{ $class->name }}</title>
    <link href="{{ asset('css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vazir.css') }}?v={{ filemtime(public_path('css/vazir.css')) }}" rel="stylesheet">
    <style>
        /* ---------- استایل عمومی ---------- */
        .report-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .report-cover {
            padding: 25px;
            border: 2px solid #343a40;
            border-radius: 8px;
            background: white;
            margin-bottom: 30px;
        }

        .student-page {
            padding: 25px;
            border: 1px solid #adb5bd;
            border-radius: 8px;
            background: white;
            margin-bottom: 30px;
        }

        .report-header {
            text-align: center;
            border-bottom: 2px solid #343a40;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            height: 100%;
        }

        .stat-box .value {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 5px 0;
        }

        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        table {
            font-size: 0.85rem;
        }

        /* ---------- تنظیمات چاپ ---------- */
        @media print {
            @page {
                size: A3;
                margin: 8mm;
            }

            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
                font-size: 10pt;
            }

            .report-cover,
            .student-page {
                border: 1px solid #6c757d !important;
                box-shadow: none !important;
                margin-bottom: 0 !important;
            }

            .report-cover {
                page-break-after: always;
                break-after: page;
            }

            /* هر دانش‌آموز یک صفحه A3 افقی می‌گیرد */
            .student-page {
                page-break-before: always;
                break-before: page;
            }

            .stat-box .value {
                font-size: 1.3rem;
            }

            table {
                font-size: 9pt;
            }

            .badge {
                border: 1px solid #6c757d;
                color: #000 !important;
                background: #f8f9fa !important;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            h1,
            h2,
            h3,
            h4,
            h5 {
                page-break-after: avoid;
                break-after: avoid;
            }

            .text-success {
                color: #198754 !important;
            }

            .text-danger {
                color: #dc3545 !important;
            }

            .text-warning {
                color: #b45309 !important;
            }

            /* چیدمان دو ستونه در A3 افقی برای صفحه هر دانش‌آموز */
            .student-page .print-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }

            /* جدول‌های بزرگ در چاپ، تمام عرض بگیرند */
            .student-page table {
                width: 100%;
            }

            .student-page table {
                break-inside: auto;
                page-break-inside: auto;
            }

            .student-page tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .student-page thead {
                display: table-header-group;
            }

            .student-page h5 {
                break-after: avoid;
                page-break-after: avoid;
            }

            /* فشرده‌تر برای چاپ */
            .student-page table {
                font-size: 8.5pt;
            }

            .student-page td,
            .student-page th {
                padding: 2px 5px;
            }

            .student-page .badge {
                font-size: 7.5pt;
                padding: 1px 5px;
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid my-3 report-container">

        {{-- نوار ابزار - در چاپ دیده نمی‌شود --}}
        <div class="no-print mb-3 d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route((auth()->user()->is_teacher ? 'teacher' : (auth()->user()->is_school_manager ? 'manager' : 'admin')) . '.classes.report', $class) }}" class="btn btn-outline-secondary">
                ← بازگشت به گزارش
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                🖨️ چاپ / ذخیره PDF
            </button>
            <span class="text-muted small">
                در پنجره چاپ، مقصد را «Save as PDF» یا «Microsoft Print to PDF» انتخاب کنید.
                تنظیمات پیشنهادی: A4، Portrait، Margins: Default.
            </span>
        </div>

        {{-- ==================== صفحه کاور ==================== --}}
        <div class="report-cover avoid-break">

            <div class="report-header">
                <h2 class="mb-1">گزارش وضعیت کلاس</h2>
                <h1 class="mb-3">{{ $class->name }}</h1>
                <div class="small">
                    <div><strong>مدرسه:</strong> {{ $class->school?->name ?? '—' }}</div>
                    <div><strong>سال تحصیلی:</strong> {{ $class->academicYear?->title ?? '—' }}</div>
                    <div><strong>پایه:</strong> {{ $class->grade?->name ?? '—' }}</div>
                    <div><strong>معلم:</strong> {{ $class->teacher?->name ?? '—' }}</div>
                    <div>
                        <strong>بازه گزارش:</strong>
                        از {{ jdate($fromDate, 'd MMMM yyyy') }} تا {{ jdate($toDate, 'd MMMM yyyy') }}
                    </div>
                    <div><strong>تاریخ تهیه:</strong> {{ jdate(now(), 'EEEE d MMMM yyyy') }}</div>
                </div>
            </div>

            <h4 class="mb-2">خلاصه کلاس</h4>
            <div class="row g-2 mb-4">
                <div class="col">
                    <div class="stat-box">
                        <div class="small text-muted">تعداد دانش‌آموز</div>
                        <div class="value">{{ $summary['students_count'] }}</div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-box">
                        <div class="small text-muted">میانگین جمع امتیاز دروس</div>
                        <div class="value {{ $summary['avg_points'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $summary['avg_points'] > 0 ? '+' : '' }}{{ $summary['avg_points'] }}
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-box">
                        <div class="small text-muted">میانگین حضور</div>
                        <div class="value">{{ $summary['avg_percentage'] }}٪</div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-box">
                        <div class="small text-muted">جمع غیبت‌ها</div>
                        <div class="value text-danger">{{ $summary['total_absences'] }}</div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-box">
                        <div class="small text-muted">جمع تاخیرها</div>
                        <div class="value" style="color:#b45309;">{{ $summary['total_lates'] }}</div>
                    </div>
                </div>
            </div>

            <h4 class="mb-2">امتیاز به تفکیک درس</h4>
            <table class="table table-sm table-bordered align-middle mb-4">
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
                            <td>{{ $ss['subject_name'] }}</td>
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

            <h4 class="mb-2">جدول رتبه‌بندی کلاس</h4>
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 55px;">رتبه</th>
                        <th>نام دانش‌آموز</th>
                        @foreach($classSubjects as $cs)
                            <th class="text-center">{{ $cs->subject?->name ?? '—' }}</th>
                        @endforeach
                        <th class="text-center">جمع دروس</th>
                        <th class="text-center">نمره رتبه</th>
                        <th class="text-center">درصد حضور</th>
                        <th class="text-center">غایب</th>
                        <th class="text-center">تاخیر</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranking as $row)
                    @php
                    $medal = match($row['rank']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                    @endphp
                    <tr>
                        <td class="text-center">{{ $medal }} {{ $row['rank'] }}</td>
                        <td>{{ $row['student']->name }}</td>
                        @foreach($classSubjects as $cs)
                            @php $subjectPoint = $row['by_subject']->get($cs->id); @endphp
                            <td class="text-center">
                                @if($subjectPoint)
                                    <span class="badge bg-{{ $subjectPoint['total'] >= 0 ? 'success' : 'danger' }}">
                                        {{ $subjectPoint['total'] > 0 ? '+' : '' }}{{ $subjectPoint['total'] }}
                                    </span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center">
                            <strong class="{{ $row['points'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $row['points'] > 0 ? '+' : '' }}{{ $row['points'] }}
                            </strong>
                            @if($row['positive_sum'] > 0 || $row['negative_sum'] < 0)
                                <br><small class="text-muted">
                                    (<span class="text-success">+{{ $row['positive_sum'] }}</span> /
                                    <span class="text-danger">{{ $row['negative_sum'] }}</span>)
                                </small>
                                @endif
                        </td>
                        <td class="text-center">
                            @if(!is_null($row['rank_score'] ?? null))
                                <strong>{{ number_format($row['rank_score'], 1) }}</strong>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $row['percentage'] }}٪</td>
                        <td class="text-center">{{ $row['stats']['absent'] }}</td>
                        <td class="text-center" style="color:#b45309;">{{ $row['stats']['late'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 7 + $classSubjects->count() }}" class="text-center text-muted">هیچ دانش‌آموزی در این کلاس ثبت‌نام نشده است.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

        </div>

        {{-- ==================== صفحه هر دانش‌آموز ==================== --}}
        @foreach($studentDetails as $detail)
        <div class="student-page">

            <div class="report-header">
                <h3 class="mb-1">وضعیت کلی دانش‌آموز</h3>
                <h2 class="mb-2">{{ $detail['student']->name }}</h2>
                <div class="small">
                    <div>
                        <strong>رتبه در کلاس:</strong>
                        {{ match($detail['rank']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' } }}
                        {{ $detail['rank'] }} از {{ $summary['students_count'] }}
                    </div>
                    <div><strong>کلاس:</strong> {{ $class->name }} — <strong>مدرسه:</strong> {{ $class->school?->name }}</div>
                    <div><strong>بازه:</strong> {{ jdate($fromDate, 'd MMMM') }} تا {{ jdate($toDate, 'd MMMM yyyy') }}</div>
                </div>
            </div>

            {{-- کارت‌های خلاصه --}}
            <div class="row g-2 mb-3">
                <div class="col">
                    <div class="stat-box">
                        <div class="small text-muted">جمع امتیاز دروس</div>
                        <div class="value {{ $detail['points']['total'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $detail['points']['total'] > 0 ? '+' : '' }}{{ $detail['points']['total'] }}
                        </div>
                        @if($detail['positive_sum'] > 0 || $detail['negative_sum'] < 0)
                            <small class="text-muted">
                            (<span class="text-success">+{{ $detail['positive_sum'] }}</span> /
                            <span class="text-danger">{{ $detail['negative_sum'] }}</span>)
                            </small>
                            @endif
                    </div>
                </div>
                <div class="col">
                    <div class="stat-box">
                        <div class="small text-muted">درصد حضور</div>
                        <div class="value text-{{ $detail['status']['color'] }}">{{ $detail['percentage'] }}٪</div>
                        <small class="text-muted">{{ $detail['status']['label'] }}</small>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-box">
                        <div class="small text-muted">غیبت</div>
                        <div class="value text-danger">{{ $detail['stats']['absent'] }}</div>
                        <small class="text-muted">جلسه</small>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-box">
                        <div class="small text-muted">تاخیر</div>
                        <div class="value" style="color:#b45309;">{{ $detail['stats']['late'] }}</div>
                        <small class="text-muted">جلسه</small>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-box">
                        <div class="small text-muted">نمره رتبه</div>
                        <div class="value">{{ !is_null($detail['rank_score'] ?? null) ? number_format($detail['rank_score'], 1) : '—' }}</div>
                        <small class="text-muted">از ۱۰۰</small>
                    </div>
                </div>
            </div>

            @if(!empty($detail['rank_components'] ?? []))
                @php $rc = $detail['rank_components']; @endphp
                <div class="mb-3">
                    <h5 class="mb-2">ترکیب نمره رتبه</h5>
                    <table class="table table-sm table-bordered align-middle mb-0">
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
                                <td class="text-center">{{ $rc['exams']['score'] !== null ? number_format($rc['exams']['score'], 1) : '—' }}</td>
                                <td class="text-center">{{ (int) round($rc['exams']['weight'] * 100) }}٪</td>
                                <td class="text-center small text-muted">
                                    {{ $rc['exams']['counted'] }} آزمون
                                    @if($rc['exams']['missed'] > 0) · <span class="text-danger">{{ $rc['exams']['missed'] }} عدم شرکت</span> @endif
                                </td>
                            </tr>
                            <tr>
                                <td>تمرین‌ها</td>
                                <td class="text-center">{{ $rc['exercises']['score'] !== null ? number_format($rc['exercises']['score'], 1) : '—' }}</td>
                                <td class="text-center">{{ (int) round($rc['exercises']['weight'] * 100) }}٪</td>
                                <td class="text-center small text-muted">
                                    {{ $rc['exercises']['counted'] }} تمرین
                                    @if($rc['exercises']['missed'] > 0) · <span class="text-danger">{{ $rc['exercises']['missed'] }} عدم شرکت</span> @endif
                                </td>
                            </tr>
                            <tr>
                                <td>حضور</td>
                                <td class="text-center">{{ $rc['attendance']['score'] !== null ? number_format($rc['attendance']['score'], 1) : '—' }}</td>
                                <td class="text-center">{{ (int) round($rc['attendance']['weight'] * 100) }}٪</td>
                                <td class="text-center small text-muted">
                                    {{ $rc['attendance']['absence_days'] }} روز غیبت از {{ $rc['attendance']['held_days'] }} روز
                                </td>
                            </tr>
                            <tr>
                                <td>امتیاز دستی</td>
                                <td class="text-center">{{ $rc['manual']['score'] !== null ? number_format($rc['manual']['score'], 1) : '—' }}</td>
                                <td class="text-center">{{ (int) round($rc['manual']['weight'] * 100) }}٪</td>
                                <td class="text-center small text-muted">{{ $rc['manual']['points'] }} امتیاز</td>
                            </tr>
                        </tbody>
                    </table>
                    <small class="text-muted d-block mt-1">
                        نمره رتبه از میانگین وزنی مؤلفه‌های موجود محاسبه می‌شود؛ مؤلفه بدون داده، وزنش بین بقیه تقسیم می‌شود.
                    </small>
                </div>
            @endif

            {{-- خلاصه به تفکیک درس --}}
            @if($detail['subjectSummary']->count() > 0)
                @php
                    $ssTotalPresent  = $detail['subjectSummary']->sum('present');
                    $ssTotalLate     = $detail['subjectSummary']->sum('late');
                    $ssTotalExcused  = $detail['subjectSummary']->sum('excused');
                    $ssTotalAbsent   = $detail['subjectSummary']->sum('absent');
                    $ssTotalHeld     = $detail['subjectSummary']->sum('total_held');
                    $ssTotalPresentC = $ssTotalPresent + $ssTotalLate + $ssTotalExcused;
                    $ssTotalPercent  = $ssTotalHeld > 0 ? round(($ssTotalPresentC / $ssTotalHeld) * 100, 1) : 0;
                    $ssTotalPoints   = $detail['subjectSummary']->sum('points');
                    $ssTotalPositive = $detail['subjectSummary']->sum('points_positive');
                    $ssTotalNegative = $detail['subjectSummary']->sum('points_negative');
                    $ssTotalCount    = $detail['subjectSummary']->sum('points_count');
                    $ssTotalTaken    = $detail['subjectSummary']->sum('exams_taken');
                    $ssTotalExams    = $detail['subjectSummary']->sum('exams_total');

                    $ssAllAvgs = $detail['subjectSummary']->pluck('avg_exam')->filter();
                    $ssTotalAvg = $ssAllAvgs->isNotEmpty() ? round($ssAllAvgs->avg(), 1) : null;

                    $ssTotalColor = $ssTotalPercent >= 95 ? 'success' : ($ssTotalPercent >= 85 ? 'info' : ($ssTotalPercent >= 70 ? 'warning text-dark' : 'danger'));
                    $ssTotalLabel = $ssTotalPercent >= 95 ? 'عالی' : ($ssTotalPercent >= 85 ? 'خوب' : ($ssTotalPercent >= 70 ? 'نیاز به بهبود' : 'هشدار'));
                @endphp

                <div class="mt-3 avoid-break">
                    <h5>خلاصه به تفکیک درس</h5>
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th colspan="10" class="text-end small" style="background:#f8f9fa; font-weight:bold;">
                                    {{ $detail['student']->name }} — خلاصه درس‌ها
                                </th>
                            </tr>
                            <tr>
                                <th>درس</th>
                                <th class="text-center" style="width: 80px;">درصد حضور</th>
                                <th class="text-center" style="width: 60px;">غایب</th>
                                <th class="text-center" style="width: 80px;">امتیاز خالص</th>
                                <th class="text-center" style="width: 70px;">مثبت</th>
                                <th class="text-center" style="width: 70px;">منفی</th>
                                <th class="text-center" style="width: 60px;">تعداد</th>
                                <th class="text-center" style="width: 90px;">میانگین آزمون</th>
                                <th class="text-center" style="width: 70px;">آزمون‌ها</th>
                                <th class="text-center" style="width: 100px;">وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detail['subjectSummary'] as $ss)
                                <tr>
                                    <td><small>{{ $ss['subject_name'] }}</small></td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $ss['percentage'] >= 85 ? 'success' : ($ss['percentage'] >= 70 ? 'warning text-dark' : 'danger') }}">
                                            {{ $ss['percentage'] }}٪
                                        </span>
                                    </td>
                                    <td class="text-center {{ $ss['absent'] > 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ $ss['absent'] }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $ss['points'] >= 0 ? 'success' : 'danger' }}">
                                            {{ $ss['points'] > 0 ? '+' : '' }}{{ $ss['points'] }}
                                        </span>
                                    </td>
                                    <td class="text-center text-success">+{{ $ss['points_positive'] }}</td>
                                    <td class="text-center text-danger">{{ $ss['points_negative'] }}</td>
                                    <td class="text-center text-muted">{{ $ss['points_count'] }}</td>
                                    <td class="text-center">
                                        @if($ss['avg_exam'] !== null)
                                            <span class="badge bg-{{ $ss['avg_exam'] >= 17 ? 'success' : ($ss['avg_exam'] >= 10 ? 'warning text-dark' : 'danger') }}">
                                                {{ $ss['avg_exam'] }} / 20
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        {{ $ss['exams_taken'] }} / {{ $ss['exams_total'] }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $ss['status']['color'] }}">{{ $ss['status']['label'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            {{-- سطر جمع --}}
                            <tr style="background: #f1f3f5; font-weight: bold;">
                                <td>جمع کل</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $ssTotalColor }}">{{ $ssTotalPercent }}٪</span>
                                </td>
                                <td class="text-center {{ $ssTotalAbsent > 0 ? 'text-danger' : 'text-muted' }}">
                                    {{ $ssTotalAbsent }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $ssTotalPoints >= 0 ? 'success' : 'danger' }}">
                                        {{ $ssTotalPoints > 0 ? '+' : '' }}{{ $ssTotalPoints }}
                                    </span>
                                </td>
                                <td class="text-center text-success">+{{ $ssTotalPositive }}</td>
                                <td class="text-center text-danger">{{ $ssTotalNegative }}</td>
                                <td class="text-center text-muted">{{ $ssTotalCount }}</td>
                                <td class="text-center">
                                    @if($ssTotalAvg !== null)
                                        <span class="badge bg-{{ $ssTotalAvg >= 17 ? 'success' : ($ssTotalAvg >= 10 ? 'warning text-dark' : 'danger') }}">
                                            {{ $ssTotalAvg }} / 20
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center small">
                                    {{ $ssTotalTaken }} / {{ $ssTotalExams }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $ssTotalColor }}">{{ $ssTotalLabel }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- چیدمان دو ستونه: خلاصه و دروس | امتیازات --}}
            <div class="print-grid">
                <div>
                    <div class="avoid-break mb-3">
                        <h5>خلاصه حضور و غیاب</h5>
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">حاضر</th>
                                    <th class="text-center">تاخیر</th>
                                    <th class="text-center">با اجازه</th>
                                    <th class="text-center">غایب</th>
                                    <th class="text-center">ترک کلاس</th>
                                    <th class="text-center">برگزار نشد</th>
                                    <th class="text-center">کل برگزار شده</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-center">
                                    <td>{{ $detail['stats']['present'] }}</td>
                                    <td>{{ $detail['stats']['late'] }}</td>
                                    <td>{{ $detail['stats']['excused'] }}</td>
                                    <td class="text-danger">{{ $detail['stats']['absent'] }}</td>
                                    <td>{{ $detail['stats']['left'] }}</td>
                                    <td class="text-muted">{{ $detail['stats']['cancelled'] }}</td>
                                    <td><strong>{{ $detail['stats']['total_held'] }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @if($detail['bySubject']->count() > 0)
                    <div class="avoid-break mb-3">
                        <h5>آمار به تفکیک درس</h5>
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>درس</th>
                                    <th class="text-center">حاضر</th>
                                    <th class="text-center">تاخیر</th>
                                    <th class="text-center">اجازه</th>
                                    <th class="text-center">غایب</th>
                                    <th class="text-center">درصد</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detail['bySubject'] as $s)
                                <tr>
                                    <td>{{ $s['subject_name'] }}</td>
                                    <td class="text-center">{{ $s['present'] }}</td>
                                    <td class="text-center">{{ $s['late'] }}</td>
                                    <td class="text-center">{{ $s['excused'] }}</td>
                                    <td class="text-center text-danger">{{ $s['absent'] }}</td>
                                    <td class="text-center">{{ $s['percentage'] }}٪</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                <div>
                    @if($detail['points']['count'] > 0)
                    <div class="mb-3">
                        <h5>
                            ریز امتیازات دستی ({{ $detail['points']['count'] }} مورد — جمع
                            {{ $detail['points']['total'] > 0 ? '+' : '' }}{{ $detail['points']['total'] }})
                        </h5>
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end small" style="background:#f8f9fa; font-weight:bold;">
                                        {{ $detail['student']->name }} — ریز امتیازات دستی
                                    </th>
                                </tr>
                                <tr>
                                    <th style="width: 75px;">تاریخ</th>
                                    <th>درس</th>
                                    <th>دسته</th>
                                    <th class="text-center" style="width: 55px;">امتیاز</th>
                                    <th>توضیح</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detail['points']['entries'] as $entry)
                                <tr>
                                    <td>{{ jdate($entry->entry_date, 'yy/MM/dd') }}</td>
                                    <td><small>{{ $entry->classSubject?->subject?->name ?? '—' }}</small></td>
                                    <td>{{ $entry->category?->name ?? '—' }}</td>
                                    <td class="text-center">
                                        <span class="{{ $entry->points >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $entry->points > 0 ? '+' : '' }}{{ $entry->points }}
                                        </span>
                                    </td>
                                    <td><small>{{ $entry->note }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="avoid-break mb-3">
                        <h5>ریز امتیازات دستی</h5>
                        <p class="text-muted small mb-0">در این بازه امتیازی ثبت نشده است.</p>
                    </div>
                    @endif
                </div>
            </div>

        {{-- نتایج آزمون‌ها --}}
        @if(isset($detail['examResults']) && $detail['examResults']->count() > 0)
            <div class="mt-3 avoid-break">
                <h5>نتایج آزمون‌ها ({{ $detail['examResults']->count() }} آزمون)</h5>
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th colspan="7" class="text-end small" style="background:#f8f9fa; font-weight:bold;">
                                {{ $detail['student']->name }} — نتایج آزمون‌ها
                            </th>
                        </tr>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>عنوان آزمون</th>
                            <th>درس</th>
                            <th class="text-center" style="width: 80px;">تاریخ</th>
                            <th class="text-center" style="width: 80px;">بهترین نمره</th>
                            <th class="text-center" style="width: 70px;">تلاش</th>
                            <th class="text-center" style="width: 90px;">وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detail['examResults'] as $idx => $row)
                            @php
                                $exam = $row['exam'];
                                $best = $row['best_attempt'];
                                $isComplete = $row['attempts_count'] >= $exam->max_attempts;
                            @endphp
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td><small>{{ $exam->title }}</small></td>
                                <td><small>{{ $exam->classSubject?->subject?->name ?? '—' }}</small></td>
                                <td class="text-center small">
                                    {{ $exam->start_at ? jdate($exam->start_at, 'yy/MM/dd') : '—' }}
                                </td>
                                <td class="text-center">
                                    @if($row['display_score'] !== null)
                                        <span class="badge bg-{{ $row['display_score'] >= 17 ? 'success' : ($row['display_score'] >= 10 ? 'warning text-dark' : 'danger') }}">
                                            {{ $row['display_score'] }} / 20
                                        </span>
                                        @if($row['missed'])
                                            <br><small class="text-danger">عدم شرکت</small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center small">
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="mt-3">
                <h5>نتایج آزمون‌ها</h5>
                <p class="text-muted small mb-0">هنوز آزمونی برای این دانش‌آموز برگزار نشده است.</p>
            </div>
        @endif

        {{-- تمرین‌ها --}}
        @php
            $allExercises = $detail['exerciseResults'] ?? collect();

            // فقط ارسال‌شده‌ها + ارسال‌نشده‌هایی که مهلتشان گذشته
            $submittedList  = $allExercises->filter(fn($r) => $r['submitted']);
            $expiredUnsents = $allExercises->filter(fn($r) => !$r['submitted'] && $r['exercise']->isExpired());
            $visibleExercises = $submittedList->merge($expiredUnsents)
                ->sortByDesc(fn($r) => $r['submitted_at']?->timestamp ?? 0)
                ->values();

            $totalAvailable  = $allExercises->count();
            $submittedCount  = $submittedList->count();
            $expiredUnsentsCount = $expiredUnsents->count();
            $percentage = $totalAvailable > 0 ? round(($submittedCount / $totalAvailable) * 100) : 0;
            $percentageColor = $percentage >= 80 ? 'success' : ($percentage >= 50 ? 'warning text-dark' : 'danger');
        @endphp

        @if($totalAvailable > 0)
            <div class="mt-3">
                <h5 class="d-flex justify-content-between align-items-center">
                    <span>تمرین‌ها</span>
                    <span class="badge bg-{{ $percentageColor }}" style="font-size: 0.8rem;">
                        ارسال‌شده: {{ $submittedCount }} از {{ $totalAvailable }} ({{ $percentage }}٪)
                    </span>
                </h5>

                @if($visibleExercises->count() > 0)
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th colspan="6" class="text-end small" style="background:#f8f9fa; font-weight:bold;">
                                    {{ $detail['student']->name }} — تمرین‌ها
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>عنوان تمرین</th>
                                <th>درس</th>
                                <th class="text-center" style="width: 80px;">مهلت</th>
                                <th class="text-center" style="width: 70px;">نمره</th>
                                <th class="text-center" style="width: 90px;">تاریخ ارسال</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($visibleExercises as $idx => $row)
                                @php
                                    $exercise = $row['exercise'];
                                    $isSubmitted = $row['submitted'];
                                    $isGraded = $row['is_graded'];

                                    $pct = $isGraded && $row['max_score'] > 0
                                        ? ($row['score'] / $row['max_score']) * 100
                                        : 0;
                                    $scoreColor = $pct >= 85 ? 'success' : ($pct >= 50 ? 'warning text-dark' : 'danger');
                                @endphp
                                <tr @if(!$isSubmitted) style="background: #f8f9fa;" @endif>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        <small>{{ $exercise->title }}</small>
                                        @if(!$isSubmitted)
                                            <span class="badge bg-secondary ms-1" style="font-size:0.7rem;">ارسال نشده</span>
                                        @endif
                                    </td>
                                    <td><small>{{ $exercise->classSubject?->subject?->name ?? '—' }}</small></td>
                                    <td class="text-center small">
                                        {{ $exercise->deadline_at ? jdate($exercise->deadline_at, 'yy/MM/dd') : '—' }}
                                    </td>
                                    <td class="text-center">
                                        @if($row['display_score'] !== null)
                                            <span class="badge bg-{{ $scoreColor }}">
                                                {{ $row['display_score'] }} / {{ $row['max_score'] }}
                                            </span>
                                            @if($row['missed'])
                                                <br><small class="text-danger">عدم شرکت</small>
                                            @endif
                                        @elseif($isSubmitted)
                                            <span class="badge bg-warning text-dark">در انتظار</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        @if($isSubmitted)
                                            {{ jdate($row['submitted_at'], 'yy/MM/dd') }}
                                            @if($row['is_late'])
                                                <br><span class="badge bg-danger" style="font-size:0.7rem;">تأخیر</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($expiredUnsentsCount > 0)
                        <small class="text-muted d-block mt-2">
                            ⚠️ {{ $expiredUnsentsCount }} تمرین با مهلت گذشته که ارسال نشده است.
                        </small>
                    @endif
                @else
                    <p class="text-muted small mb-0">
                        این دانش‌آموز هیچ تمرینی ارسال نکرده و هیچ مهلتی هم منقضی نشده است.
                    </p>
                @endif
            </div>
        @else
            <div class="mt-3">
                <h5>تمرین‌ها</h5>
                <p class="text-muted small mb-0">هنوز هیچ تمرینی برای این دانش‌آموز تعریف نشده است.</p>
            </div>
        @endif

        {{-- ← ریز حضور و غیاب روزانه --}}
        @php
            $grouped = $detail['groupedAttendance'] ?? collect();
            $groupedCount = $grouped->count();
            $useTwoCols = $groupedCount > 20;
            $half = (int) ceil($groupedCount / 2);
            $firstHalf = $useTwoCols ? $grouped->take($half) : $grouped;
            $secondHalf = $useTwoCols ? $grouped->slice($half) : collect();
        @endphp

        @if($groupedCount > 0)
            <div class="mt-3">
                <h5>ریز حضور و غیاب روزانه ({{ $groupedCount }} روز)</h5>

                @if($useTwoCols)
                    <div class="row g-2">
                        <div class="col-6">
                            @include('admin.classes.partials.attendance-table', ['rows' => $firstHalf])
                        </div>
                        <div class="col-6">
                            @include('admin.classes.partials.attendance-table', ['rows' => $secondHalf])
                        </div>
                    </div>
                @else
                    <div style="max-width: 50%;">
                        @include('admin.classes.partials.attendance-table', ['rows' => $firstHalf])
                    </div>
                @endif

                {{-- راهنمای ایموجی --}}
                <div class="mt-2 small text-muted" style="line-height: 1.9;">
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
        @else
            <div class="mt-3">
                <h5>ریز حضور و غیاب روزانه</h5>
                <p class="text-muted small mb-0">در این بازه هیچ جلسه‌ای ثبت نشده است.</p>
            </div>
        @endif

        </div>
        @endforeach

        <div class="text-center text-muted small my-4 no-print">
            تهیه شده در {{ jdate(now(), 'yyyy/MM/dd') }} — سامانه myclass
        </div>

    </div>

</body>

</html>
