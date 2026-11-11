@extends('layouts.panel')

@section('title', 'حضور و غیاب من')

@section('content')

    <h3 class="mb-3">حضور و غیاب من</h3>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('panel.attendance') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">از تاریخ (شمسی)</label>
                    <input type="text" name="from" class="form-control form-control-sm" value="{{ $quickFilter === 'all' ? '' : jdate($fromDate) }}" placeholder="1405/07/01" dir="ltr" data-jdp data-jdp-only-date>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">تا تاریخ (شمسی)</label>
                    <input type="text" name="to" class="form-control form-control-sm" value="{{ $quickFilter === 'all' ? '' : jdate($toDate) }}" placeholder="1405/08/09" dir="ltr" data-jdp data-jdp-only-date>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm">اعمال فیلتر</button>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('panel.attendance', ['quick' => 'all']) }}" class="btn btn-outline-secondary {{ $quickFilter === 'all' ? 'active' : '' }}">همه</a>
                        <a href="{{ route('panel.attendance', ['quick' => 'this_month']) }}" class="btn btn-outline-secondary {{ $quickFilter === 'this_month' ? 'active' : '' }}">این ماه</a>
                        <a href="{{ route('panel.attendance', ['quick' => 'last_30_days']) }}" class="btn btn-outline-secondary {{ $quickFilter === 'last_30_days' ? 'active' : '' }}">۳۰ روز</a>
                    </div>
                </div>
            </form>
            <div class="mt-2 small text-muted">
                بازه: <strong>{{ jdate($fromDate) }}</strong> تا <strong>{{ jdate($toDate) }}</strong>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card text-center"><div class="card-body">
            <div class="small text-muted">درصد حضور</div>
            <h3 class="mb-0 text-{{ $status['color'] }}">{{ $overall['percentage'] }}٪</h3>
            <span class="badge bg-{{ $status['color'] }}">{{ $status['label'] }}</span>
        </div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body">
            <div class="small text-muted">حاضر</div>
            <h3 class="mb-0 text-success">{{ $overall['present'] }}</h3>
        </div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body">
            <div class="small text-muted">غایب</div>
            <h3 class="mb-0 text-danger">{{ $overall['absent'] }}</h3>
        </div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body">
            <div class="small text-muted">تاخیر</div>
            <h3 class="mb-0" style="color:#b45309;">{{ $overall['late'] }}</h3>
        </div></div></div>
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
                            <td class="text-center"><span class="badge bg-{{ $s['percentage'] >= 85 ? 'success' : ($s['percentage'] >= 70 ? 'warning text-dark' : 'danger') }}">{{ $s['percentage'] }}٪</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">آماری موجود نیست.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- <div class="card">
        <div class="card-header"><strong>ریز غیبت‌ها و تاخیرها ({{ $absences->count() }} مورد)</strong></div>
        <div class="card-body pt-2 pb-0">
            <small class="text-muted d-block mb-2">
                ℹ️ ستون «جلسه» شمارهٔ <strong>زنگ</strong> در آن روز است، نه تعداد جلسات. «۱» یعنی زنگ اول و «۴» یعنی زنگ چهارم.
            </small>
        </div>
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>تاریخ</th>
                        <th>درس</th>
                        <th class="text-center">جلسه (زنگ)</th>
                        <th class="text-center">وضعیت</th>
                        <th>یادداشت</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($absences as $att)
                        <tr>
                            <td>{{ jdate($att->attendanceDay?->date) }}</td>
                            <td><small>{{ $att->attendanceDay?->classSubject?->subject?->name ?? '—' }}</small></td>
                            <td class="text-center"><span class="badge bg-light text-dark border">زنگ {{ $att->session_number }}</span></td>
                            <td class="text-center"><span class="badge bg-{{ $att->statusColor() }}">{{ $att->statusLabel() }}</span></td>
                            <td><small>{{ $att->note }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">هیچ غیبت یا تاخیری نداری. ✅</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div> -->

    {{-- ریز حضور و غیاب (جدول روز-محور به تفکیک درس) --}}
    @if($groupedAttendance->count() > 0)
        <div class="mt-4 mb-2">
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
                    <div style="max-width: 100%;">
                        @include('admin.classes.partials.attendance-table', ['rows' => $rows])
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="card mb-3 mt-4">
            <div class="card-header"><strong>ریز حضور و غیاب</strong></div>
            <div class="card-body">
                <p class="text-muted small mb-0">در این بازه هیچ جلسه‌ای ثبت نشده است.</p>
            </div>
        </div>
    @endif

@endsection