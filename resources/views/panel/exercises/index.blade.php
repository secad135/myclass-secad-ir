@extends('layouts.panel')

@section('title', 'تمرین‌های من')

@section('head')
<style>
    @media (max-width: 991.98px) {
        h3 { font-size: 1.15rem; }
        h5 { font-size: 1rem; margin-top: 1rem; }

        /* کارت‌های آزمون/تمرین: چیدمان عمودی */
        .card.mb-3 .card-body {
            padding: 12px 10px !important;
        }
        .card.mb-3 .d-flex.justify-content-between {
            flex-direction: column;
            gap: 12px;
        }
        .card.mb-3 h5 {
            font-size: 1rem !important;
            line-height: 1.5;
        }
        .card.mb-3 .small {
            font-size: 0.82rem;
            line-height: 1.6;
        }
        .card.mb-3 .badge {
            font-size: 0.72rem !important;
            margin-bottom: 3px;
        }
        .card.mb-3 .text-end {
            text-align: right !important;
            width: 100%;
        }
        .card.mb-3 .text-end .btn {
            width: 100%;
            min-height: 44px;
            font-size: 0.95rem;
        }
        .card.mb-3 .text-end strong[style*="1.3rem"] {
            font-size: 1.5rem !important;
        }

        /* جدول تلاش‌های قبلی */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
        .table-sm {
            font-size: 0.8rem;
            min-width: 500px;
        }
        .table-sm th,
        .table-sm td {
            padding: 6px 5px !important;
            white-space: nowrap;
        }
        .table-sm .btn-sm {
            font-size: 0.72rem;
            padding: 4px 8px;
        }
        .badge { font-size: 0.7rem !important; }
        .alert { font-size: 0.88rem; padding: 10px 12px; }
    }
</style>
@endsection

@section('content')

    <h3 class="mb-3">تمرین‌های من</h3>

    <h5 class="mb-2">تمرین‌های در دسترس</h5>

    @forelse($exercises as $exercise)
        @php
            $status = $exercise->statusLabel();
            $sub = $submissions->get($exercise->id);
        @endphp

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div class="flex-grow-1">
                        <h5 class="mb-1">{{ $exercise->title }}</h5>
                        <div class="small text-muted mb-2">
                            <span class="badge bg-info">{{ $exercise->classSubject?->subject?->name }}</span>
                            <span class="badge bg-secondary">{{ $exercise->classSubject?->schoolClass?->name }}</span>
                            <span class="badge bg-{{ $status['color'] }}">{{ $status['label'] }}</span>
                        </div>
                        @if($exercise->deadline_at)
                            <div class="small">
                                ⏰ مهلت: <strong>{{ jdate($exercise->deadline_at, 'EEEE d MMMM yyyy HH:mm') }}</strong>
                            </div>
                        @endif
                        <div class="small text-muted mt-1">
                            حداکثر نمره: <strong>{{ $exercise->max_score }}</strong>
                        </div>
                    </div>

                    <div class="text-end">
                        @if(!$sub)
                            <span class="badge bg-secondary">ارسال نشده</span>
                        @elseif($sub->isGraded())
                            <span class="badge bg-success">تصحیح شده</span>
                            <div class="mt-1">
                                <strong class="text-primary" style="font-size: 1.3rem;">{{ $sub->score }}</strong>
                                <span class="text-muted">/ {{ $exercise->max_score }}</span>
                            </div>
                        @else
                            <span class="badge bg-warning text-dark">در انتظار تصحیح</span>
                        @endif
                        <div class="mt-2">
                            <a href="{{ route('panel.exercises.show', $exercise) }}" class="btn btn-sm btn-primary">
                                {{ $sub ? 'مشاهده' : 'شرکت / پاسخ' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info">در حال حاضر تمرینی در دسترس نیست.</div>
    @endforelse

@endsection
