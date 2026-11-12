@extends('layouts.admin')

@section('title', 'نتایج آزمون: ' . $exam->title)

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">نتایج آزمون: {{ $exam->title }}</h4>
            <small class="text-muted">
                {{ $exam->classSubject?->subject?->name }} |
                {{ $exam->classSubject?->schoolClass?->name }} |
                {{ $exam->classSubject?->schoolClass?->school?->name }}
            </small>
        </div>
        <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary">بازگشت</a>
    </div>

    {{-- کارت‌های خلاصه --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center h-100"><div class="card-body">
                <div class="small text-muted">تعداد دانش‌آموز</div>
                <h3 class="mb-0">{{ $summary['students_count'] }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100"><div class="card-body">
                <div class="small text-muted">شرکت‌کننده</div>
                <h3 class="mb-0 text-success">{{ $summary['attempted_count'] }}</h3>
                <small class="text-muted">از {{ $summary['students_count'] }}</small>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100"><div class="card-body">
                <div class="small text-muted">میانگین نمره</div>
                <h3 class="mb-0 text-primary">{{ $summary['avg_score'] }}</h3>
                <small class="text-muted">از ۲۰</small>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100"><div class="card-body">
                <div class="small text-muted">بالاترین / پایین‌ترین</div>
                <h3 class="mb-0">
                    <span class="text-success">{{ $summary['highest_score'] }}</span>
                    <small class="text-muted">/</small>
                    <span class="text-danger">{{ $summary['lowest_score'] }}</span>
                </h3>
            </div></div>
        </div>
    </div>

    {{-- جدول نتایج دانش‌آموزان --}}
    <div class="card mb-4">
        <div class="card-header"><strong>نتایج دانش‌آموزان</strong></div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>دانش‌آموز</th>
                        <th class="text-center">تعداد تلاش</th>
                        <th class="text-center">بهترین نمره</th>
                        <th class="text-center">تلاش‌ها</th>
                        <th class="text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['rows'] as $idx => $row)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $row['student']->name }}</strong>
                                @if($row['in_progress'] > 0)
                                    <span class="badge bg-warning text-dark">در حال انجام</span>
                                @endif
                            </td>
                            <td class="text-center">
                                {{ $row['attempted'] }} / {{ $exam->max_attempts }}
                            </td>
                            <td class="text-center">
                                @if($row['best_score'] !== null)
                                    <span class="badge bg-{{ $row['best_score'] >= 17 ? 'success' : ($row['best_score'] >= 10 ? 'warning text-dark' : 'danger') }}" style="font-size: 0.95rem;">
                                        {{ $row['best_score'] }} / 20
                                    </span>
                                @elseif($row['in_progress'] > 0)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="text-muted small">شرکت نکرده</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row['attempts']->isEmpty())
                                    <span class="text-muted small">—</span>
                                @else
                                    <div class="d-flex gap-1 flex-wrap justify-content-center">
                                        @foreach($row['attempts'] as $att)
                                            @php
                                                $isFinished = in_array($att->status, ['submitted', 'expired']);
                                                $badgeColor = $att->status === 'submitted' ? 'primary' : ($att->status === 'expired' ? 'warning text-dark' : 'secondary');
                                                $statusText = $att->status === 'submitted' ? 'ثبت شده' : ($att->status === 'expired' ? 'پایان زمان' : 'ناتمام');
                                            @endphp
                                            <a href="{{ route('admin.exams.attempt-detail', [$exam, $att]) }}"
                                               class="badge bg-{{ $badgeColor }} text-decoration-none d-inline-flex align-items-center gap-1"
                                               title="تلاش #{{ $att->attempt_number }} — {{ $statusText }}">
                                                #{{ $att->attempt_number }}: {{ $isFinished ? $att->final_score : 'ناتمام' }}
                                                @if($att->tab_switches > 0)
                                                    <span class="badge bg-danger rounded-pill" title="تعداد خروج از صفحه">{{ $att->tab_switches }} ⚠️</span>
                                                @endif
                                            </a>
                                            @if(auth()->user()?->is_admin)
                                            <form action="{{ route('admin.exams.attempts.destroy', [$exam, $att]) }}" method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('تلاش #{{ $att->attempt_number }} دانش‌آموز «{{ $row['student']->name }}» حذف شود؟ این کار قابل بازگشت نیست.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1"
                                                        title="حذف این تلاش">
                                                    🗑️
                                                </button>
                                            </form>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row['best_attempt'])
                                    <a href="{{ route('admin.exams.attempt-detail', [$exam, $row['best_attempt']]) }}"
                                       class="btn btn-sm btn-outline-info">بهترین پاسخ</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">هیچ دانش‌آموزی در این کلاس نیست.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- تحلیل سوال‌به‌سوال --}}
    @if(!empty($analysis))
        <div class="card mb-4">
            <div class="card-header">
                <strong>تحلیل سوال‌به‌سوال</strong>
                <small class="text-muted">— مرتب‌شده از سخت‌ترین به آسان‌ترین</small>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>متن سوال</th>
                            <th class="text-center" style="width: 70px;">پاسخ صحیح</th>
                            <th class="text-center" style="width: 60px;">غلط</th>
                            <th class="text-center" style="width: 70px;">بدون پاسخ</th>
                            <th class="text-center" style="width: 90px;">نرخ صحیح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analysis as $idx => $item)
                            @php
                                $rate = $item['correct_rate'];
                                $color = $rate >= 70 ? 'success' : ($rate >= 40 ? 'warning' : 'danger');
                            @endphp
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <div style="max-width: 600px;">{{ Str::limit($item['question']->text, 120) }}</div>
                                    @if($item['question']->code_snippet)
                                        <small class="text-muted">📄 شامل کد</small>
                                    @endif
                                </td>
                                <td class="text-center text-success">
                                    {{ $item['correct'] }} / {{ $item['total'] }}
                                </td>
                                <td class="text-center text-danger">{{ $item['wrong'] }}</td>
                                <td class="text-center text-muted">{{ $item['unanswered'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $color }}">{{ $rate }}٪</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-info">هنوز هیچ دانش‌آموزی در این آزمون شرکت نکرده است.</div>
    @endif

@endsection
