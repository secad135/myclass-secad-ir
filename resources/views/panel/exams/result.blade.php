@extends('layouts.panel')

@section('title', 'نتیجه آزمون — ' . $attempt->exam->title)

@section('head')
<style>
    @media (max-width: 991.98px) {
        .container-fluid {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        /* کارت‌های خلاصه */
        .card .row.g-3 > [class*="col-"] {
            width: 100%;
            margin-bottom: 8px;
        }
        .card .row.g-3 > [class*="col-"]:last-child {
            margin-bottom: 0;
        }

        /* نمره نهایی */
        .card .row.g-3 .border h1 {
            font-size: 2rem;
        }
        .card .row.g-3 .border h2 {
            font-size: 1.4rem;
        }

        /* پیشرفت */
        .progress { height: 30px !important; }

        /* مرور سوال */
        .card.mb-3 .card-body {
            padding: 12px 10px !important;
        }
        .card.mb-3 .card-body > .d-flex > .d-flex > .fw-bold + div {
            font-size: 0.98rem;
            line-height: 1.7;
        }

        /* گزینه‌های مرور */
        .card .border.rounded.p-2 {
            padding: 10px !important;
            font-size: 0.92rem !important;
            flex-wrap: wrap;
            gap: 4px;
        }
        .card .border.rounded.p-2 .badge {
            font-size: 0.72rem;
        }

        /* کد در مرور */
        .card pre.bg-dark {
            font-size: 0.78rem !important;
            padding: 10px !important;
            -webkit-overflow-scrolling: touch;
            overflow-x: auto;
        }

        /* هدر */
        .d-flex.justify-content-between h3 {
            font-size: 1.1rem !important;
        }

        /* بج‌های سوال */
        .card .badge {
            font-size: 0.78rem;
        }
    }

    @media print {
        .btn { display: none !important; }
    }
    @media (max-width: 991.98px) {
        /* جلوگیری از بیرون زدن عرض صفحه */
        body, .container-fluid, .card, .card-body, .row, [class*="col-"] {
            max-width: 100% !important;
            overflow-x: hidden !important;
        }

        /* اصلاح گزینه‌ها (حل مشکل شکسته نشدن متن و کد) */
        .card .border.rounded.p-2 {
            flex-wrap: wrap !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }
        .card .border.rounded.p-2 .flex-grow-1 {
            white-space: normal !important;
            word-break: break-word !important;
            font-size: 0.9rem !important;
        }

        /* اصلاح کدهای داخل سوال */
        .card pre.bg-dark, .code-snippet {
            max-width: 100% !important;
            overflow-x: auto !important;
            white-space: pre-wrap !important; /* اجازه شکستن خطوط کد */
            word-break: break-all !important;
            font-size: 0.8rem !important;
        }

        /* اصلاح کارت‌های بالای صفحه (نمره و...) */
        .card .row.g-3 > [class*="col-"] {
            margin-bottom: 10px;
        }

        /* اجبار به ماندن کدها داخل کادر */
        .code-block-wrapper, pre {
            max-width: 100% !important;
            overflow-x: auto !important;
        }
        .card {
            overflow: hidden !important; /* جلوگیری از بیرون زدن محتوا از کارت */
        }
    }
</style>
@endsection

@section('content')

    @php
        $exam = $attempt->exam;
        $score = $attempt->final_score;
        $scoreColor = $score >= 17 ? 'success' : ($score >= 10 ? 'warning' : 'danger');
        $unanswered = $attempt->answers->whereNull('selected_option_id')->count();
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">نتیجه آزمون</h3>
        <a href="{{ route('panel.exams.index') }}" class="btn btn-secondary">بازگشت به آزمون‌ها</a>
    </div>

    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    <div class="card mb-4 border-{{ $scoreColor }}">
        <div class="card-body text-center">
            <h4>{{ $exam->title }}</h4>
            <p class="text-muted small mb-3">
                {{ $exam->classSubject?->subject?->name }} |
                تلاش #{{ $attempt->attempt_number }} |
                {{ jdate($attempt->finished_at, 'EEEE d MMMM yyyy HH:mm') }}
            </p>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="small text-muted">نمره شما</div>
                        @if($attempt->is_approved)
                            <h1 class="mb-0 text-{{ $scoreColor }}">{{ $attempt->teacher_score }}</h1>
                            <small class="text-success">✓ تایید شده توسط معلم</small>
                        @else
                            <h1 class="mb-0 text-muted" style="opacity: 0.6;">{{ $attempt->final_score }}</h1>
                            <small class="text-warning">⏳ در انتظار تایید معلم</small>
                        @endif
                        <small class="text-muted">از ۲۰</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="small text-muted">پاسخ صحیح</div>
                        <h2 class="mb-0 text-success">{{ $attempt->total_correct }}</h2>
                        <small class="text-muted">از {{ $attempt->total_questions }}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="small text-muted">بدون پاسخ</div>
                        <h2 class="mb-0 text-secondary">{{ $unanswered }}</h2>
                        <small class="text-muted">سوال</small>
                    </div>
                </div>
            </div>

            <div class="progress" style="height: 25px;">
                <div class="progress-bar bg-{{ $scoreColor }}" style="width: {{ ($score / 20) * 100 }}%;">
                    {{ $score }} / 20
                </div>
            </div>

            @if($attempt->status === 'expired')
                <div class="alert alert-warning small mt-3 mb-0">
                    ⏰ این آزمون به دلیل پایان زمان، به‌طور خودکار ثبت شد.
                </div>
            @endif
        </div>
    </div>

    {{-- مرور پاسخ‌ها --}}
    @if($exam->show_result_immediately)
        <h5 class="mb-3">مرور پاسخ‌ها</h5>

        @foreach($attempt->answers as $idx => $answer)
            @php
                $question = $answer->question;
                $correctOption = $question->options->firstWhere('is_correct', true);
                $selectedOption = $answer->selectedOption;
                $options = collect($answer->options_order)
                    ->map(fn($id) => $question->options->firstWhere('id', $id))
                    ->filter();
            @endphp

            <div class="card mb-3 border-{{ $answer->is_correct ? 'success' : 'danger' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-start gap-2 flex-grow-1">
                            <span class="badge bg-{{ $answer->is_correct ? 'success' : 'danger' }}">#{{ $idx + 1 }}</span>
                            <div>
                                <div>{!! nl2br(e($question->text)) !!}</div>
                                @if($question->code_snippet)
                                    <x-code-block :code="$question->code_snippet" label="کد" />
                                @endif
                            </div>
                        </div>
                        <div class="ms-2">
                            @if($answer->is_correct)
                                <span class="badge bg-success">✓ صحیح</span>
                            @else
                                <span class="badge bg-danger">✗ غلط</span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3">
                        @foreach($options as $optIdx => $option)
                            @php
                                $isCorrect = $option->id === $correctOption?->id;
                                $isSelected = $selectedOption && $option->id === $selectedOption->id;
                            @endphp
                            <div class="border rounded p-2 mb-1 d-flex align-items-center
                                {{ $isCorrect ? 'border-success bg-success-subtle' : '' }}
                                {{ $isSelected && !$isCorrect ? 'border-danger bg-danger-subtle' : '' }}">
                                <span class="fw-bold me-2">{{ $optIdx + 1 }})</span>
                                <span class="flex-grow-1" style="font-family: 'Courier New', monospace;">{{ $option->text }}</span>
                                @if($isCorrect)
                                    <span class="badge bg-success ms-2">✓ پاسخ صحیح</span>
                                @endif
                                @if($isSelected && !$isCorrect)
                                    <span class="badge bg-danger ms-2">✗ پاسخ شما</span>
                                @endif
                                @if($isSelected && $isCorrect)
                                    <span class="badge bg-success ms-2">✓ پاسخ شما</span>
                                @endif
                            </div>
                        @endforeach

                        @if(!$selectedOption)
                            <div class="alert alert-secondary small mt-2 mb-0">
                                شما به این سوال پاسخ نداده‌اید.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-info">
            مرور پاسخ‌ها پس از بررسی توسط معلم، در دسترس خواهد بود.
        </div>
    @endif

@endsection
