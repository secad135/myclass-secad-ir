@extends('layouts.panel')

@section('title', 'امتیازات من')

@section('content')

    <h3 class="mb-3">امتیازات من</h3>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('panel.points') }}" class="row g-2 align-items-end">
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
                        <a href="{{ route('panel.points', ['quick' => 'all']) }}" class="btn btn-outline-secondary {{ $quickFilter === 'all' ? 'active' : '' }}">همه</a>
                        <a href="{{ route('panel.points', ['quick' => 'this_month']) }}" class="btn btn-outline-secondary {{ $quickFilter === 'this_month' ? 'active' : '' }}">این ماه</a>
                        <a href="{{ route('panel.points', ['quick' => 'last_30_days']) }}" class="btn btn-outline-secondary {{ $quickFilter === 'last_30_days' ? 'active' : '' }}">۳۰ روز</a>
                    </div>
                </div>
            </form>
            <div class="mt-2 small text-muted">
                بازه: <strong>{{ jdate($fromDate) }}</strong> تا <strong>{{ jdate($toDate) }}</strong>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card text-center"><div class="card-body">
            <div class="small text-muted">جمع امتیاز دروس</div>
            <h2 class="mb-0 text-{{ $points['total'] >= 0 ? 'success' : 'danger' }}">{{ $points['total'] > 0 ? '+' : '' }}{{ $points['total'] }}</h2>
        </div></div></div>
        <div class="col-md-4"><div class="card text-center"><div class="card-body">
            <div class="small text-muted">جمع مثبت</div>
            <h3 class="mb-0 text-success">+{{ $points['positive'] }}</h3>
        </div></div></div>
        <div class="col-md-4"><div class="card text-center"><div class="card-body">
            <div class="small text-muted">جمع منفی</div>
            <h3 class="mb-0 text-danger">{{ $points['negative'] }}</h3>
        </div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>امتیاز به تفکیک درس</strong>
            <small class="text-muted">جمع هر درس جداگانه محاسبه می‌شود</small>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">درسی برای شما یافت نشد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>ریز امتیازات ({{ $points['count'] }} مورد)</strong></div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>تاریخ</th>
                        <th>درس</th>
                        <th>دسته</th>
                        <th class="text-center">امتیاز</th>
                        <th>توضیح</th>
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
                            <td class="text-center"><span class="badge bg-{{ $entry->points >= 0 ? 'success' : 'danger' }}">{{ $entry->points > 0 ? '+' : '' }}{{ $entry->points }}</span></td>
                            <td><small>{{ $entry->note }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">هنوز امتیازی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection