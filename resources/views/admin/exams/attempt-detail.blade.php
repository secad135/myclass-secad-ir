@extends('layouts.admin')

@section('title', 'جزئیات تلاش — ' . $attempt->student->name)

@section('head')
<style>
    @media (max-width: 991.98px) {
        body, .container-fluid, .card, .card-body, .row, [class*="col-"] {
            max-width: 100% !important;
            overflow-x: hidden !important;
        }

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

        .card pre.bg-dark, .code-snippet {
            max-width: 100% !important;
            overflow-x: auto !important;
            white-space: pre-wrap !important;
            word-break: break-all !important;
            font-size: 0.8rem !important;
        }

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
        $score = $attempt->final_score;
        $scoreColor = $score >= 17 ? 'success' : ($score >= 10 ? 'warning' : 'danger');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">جزئیات پاسخ‌های {{ $attempt->student->name }}</h4>
            <small class="text-muted">
                آزمون: {{ $exam->title }} | تلاش #{{ $attempt->attempt_number }} |
                {{ jdate($attempt->finished_at ?? $attempt->started_at, 'yyyy/MM/dd HH:mm') }}
            </small>
        </div>
        <a href="{{ route('admin.exams.results', $exam) }}" class="btn btn-secondary">بازگشت به نتایج</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-center border-{{ $scoreColor }}"><div class="card-body">
                <div class="small text-muted">نمره</div>
                <h2 class="mb-0 text-{{ $scoreColor }}">{{ $score }} / 20</h2>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-center"><div class="card-body">
                <div class="small text-muted">پاسخ صحیح</div>
                <h2 class="mb-0 text-success">{{ $attempt->total_correct }} / {{ $attempt->total_questions }}</h2>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-center"><div class="card-body">
                <div class="small text-muted">وضعیت</div>
                <h4 class="mb-0">
                    @if($attempt->status === 'submitted')
                        <span class="badge bg-success">ثبت شده</span>
                    @elseif($attempt->status === 'expired')
                        <span class="badge bg-warning text-dark">پایان زمان</span>
                    @else
                        <span class="badge bg-secondary">ناتمام</span>
                    @endif
                </h4>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-{{ $attempt->is_approved ? 'success' : 'warning' }}">
                <div class="card-body">
                    <div class="small text-muted">نمره نهایی (تایید معلم)</div>
                    @if($attempt->is_approved)
                        <h3 class="mb-0 text-success">{{ $attempt->teacher_score }} / 20</h3>
                        <small class="text-success">✓ تایید شده</small>
                    @else
                        <form action="{{ route('admin.exams.approve-score', $attempt) }}" method="POST" class="mt-2 d-flex gap-2">
                            @csrf
                            <input type="number" name="teacher_score" class="form-control form-control-sm"
                                   value="{{ $attempt->score }}" step="0.25" min="0" max="20" required>
                            <button class="btn btn-sm btn-success">تایید</button>
                        </form>
                        <small class="text-warning mt-1 d-block">در انتظار تایید (نمره خودکار: {{ $attempt->score }})</small>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-{{ $attempt->tab_switches > 0 ? 'danger' : 'success' }}">
                <div class="card-body">
                    <div class="small text-muted">وضعیت امنیتی</div>
                    @if($attempt->tab_switches > 0)
                        <h4 class="mb-0 text-danger">{{ $attempt->tab_switches }} تخلف</h4>
                        <small class="text-danger">خروج از صفحه</small>
                    @else
                        <h4 class="mb-0 text-success">سالم</h4>
                        <small class="text-success">بدون تخلف</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <h5>مرور سوالات</h5>

    @foreach($attempt->answers as $idx => $answer)
        @php
            $question = $answer->question;
            $correctOption = $question->options->firstWhere('is_correct', true);
            $selectedOption = $answer->selectedOption;
            $options = collect($answer->options_order)
                ->map(fn($id) => $question->options->firstWhere('id', $id))
                ->filter();
        @endphp

        <div class="card mb-3 border-{{ $answer->is_correct ? 'success' : ($selectedOption ? 'danger' : 'secondary') }}">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <div class="d-flex align-items-start gap-2 flex-grow-1">
                        <span class="badge bg-{{ $answer->is_correct ? 'success' : ($selectedOption ? 'danger' : 'secondary') }}">
                            #{{ $idx + 1 }}
                        </span>
                        <div>
                            <div>{!! nl2br(e($question->text)) !!}</div>
                            @if($question->code_snippet)
                                <x-code-block :code="$question->code_snippet" label="کد" />
                            @endif
                        </div>
                    </div>
                    <div>
                        @if($answer->is_correct)
                            <span class="badge bg-success">✓ صحیح</span>
                        @elseif(!$selectedOption)
                            <span class="badge bg-secondary">بدون پاسخ</span>
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
                                <span class="badge bg-success ms-2">✓ صحیح</span>
                            @endif
                            @if($isSelected)
                                <span class="badge bg-{{ $isCorrect ? 'success' : 'danger' }} ms-2">پاسخ دانش‌آموز</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

@endsection
