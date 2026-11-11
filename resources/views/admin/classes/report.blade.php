@php
$isManager = auth()->user()->is_school_manager;
$layout = $isManager ? 'layouts.manager' : 'layouts.admin';
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
        @if(!auth()->user()->is_school_manager)
        <a href="{{ route('admin.classes.full-report', ['class' => $class, 'quick' => $quickFilter ?? 'all']) }}" class="btn btn-success">📄 گزارش کامل برای چاپ</a>
        <a href="{{ route('admin.classes.show', $class) }}" class="btn btn-outline-secondary">بازگشت</a>
        @else
        <a href="{{ route('manager.dashboard') }}" class="btn btn-outline-secondary">بازگشت به داشبورد</a>
        @endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $isManager ? route('manager.classes.report', $class) : route('admin.classes.report', $class) }}" class="row g-2 align-items-end">
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
                    <a href="{{ route($isManager ? 'manager.classes.report' : 'admin.classes.report', ['class' => $class, 'quick' => 'all']) }}" class="btn btn-outline-secondary {{ ($quickFilter ?? 'all') === 'all' ? 'active' : '' }}">همه (سال تحصیلی)</a>
                    <a href="{{ route($isManager ? 'manager.classes.report' : 'admin.classes.report', ['class' => $class, 'quick' => 'this_month']) }}" class="btn btn-outline-secondary {{ $quickFilter === 'this_month' ? 'active' : '' }}">این ماه</a>
                    <a href="{{ route($isManager ? 'manager.classes.report' : 'admin.classes.report', ['class' => $class, 'quick' => 'last_30_days']) }}" class="btn btn-outline-secondary {{ $quickFilter === 'last_30_days' ? 'active' : '' }}">۳۰ روز گذشته</a>
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
                <div class="small text-muted">میانگین امتیاز</div>
                <h3 class="mb-0 text-{{ $summary['avg_points'] >= 0 ? 'success' : 'danger' }}">{{ $summary['avg_points'] > 0 ? '+' : '' }}{{ $summary['avg_points'] }}</h3>
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

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <strong>جدول رتبه‌بندی</strong>
        <small class="text-muted">مرتب‌شده بر اساس امتیاز، سپس درصد حضور</small>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width: 60px;">رتبه</th>
                    <th>دانش‌آموز</th>
                    <th class="text-center">امتیاز دستی</th>
                    <th class="text-center">درصد حضور</th>
                    <th class="text-center">غایب</th>
                    <th class="text-center">تاخیر</th>
                    <th class="text-center">اجازه</th>
                    <th class="text-center">کل جلسات</th>
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
                    <td class="text-center">
                        <span class="badge bg-{{ $row['points'] >= 0 ? 'success' : 'danger' }}" style="font-size: 0.95rem;">{{ $row['points'] > 0 ? '+' : '' }}{{ $row['points'] }}</span>
                        @if($row['positive_sum'] > 0 || $row['negative_sum'] < 0)
                            <br><small class="text-muted" style="font-size: 0.75rem;">
                                (<span class="text-success">+{{ $row['positive_sum'] }}</span> / <span class="text-danger">{{ $row['negative_sum'] }}</span>)
                            </small>
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
                    <td class="text-center text-muted">{{ $row['stats']['excused'] }}</td>
                    <td class="text-center text-muted">{{ $row['stats']['total_held'] }}</td>
                    <td class="text-center">
                        <a href="{{ auth()->user()->is_school_manager 
        ? route('manager.students.show', ['student' => $row['student'], 'class_id' => $class->id]) 
        : route('admin.students.show', ['student' => $row['student'], 'class_id' => $class->id]) }}"
                            class="btn btn-sm btn-outline-info">
                            وضعیت کلی
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">برای این کلاس در این بازه هیچ داده‌ای وجود ندارد.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection