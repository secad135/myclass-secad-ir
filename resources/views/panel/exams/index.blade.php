@extends('layouts.panel')

@section('title', 'آزمون‌ها')

@section('head')
<style>
    .exam-card { border: 1px solid #e9ecef; border-radius: 12px; transition: all 0.2s; }
    .exam-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-color: #0d6efd; }
    .exam-card.new { border-right: 4px solid #198754; }
    .exam-card.done { border-right: 4px solid #6c757d; }
    .exam-card.retry { border-right: 4px solid #fd7e14; }
    .info-badge { font-size: 0.78rem; padding: 4px 10px; border-radius: 20px; }
</style>
@endsection

@section('content')

    <h3 class="mb-3">آزمون‌ها</h3>

    <!-- بنر اطلاع‌رسانی -->
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
        <span style="font-size: 1.5rem;">💡</span>
        <div>
            <strong>توجه:</strong>
            در صورت شرکت چندباره در یک آزمون، فقط <strong>بهترین نمره</strong> شما در کارنامه ثبت می‌شود.
            شما می‌توانید تا سقف تعیین‌شده توسط معلم، در آزمون شرکت کنید و نمره خود را بهتر کنید.
        </div>
    </div>

    <h5 class="mb-3">📋 آزمون‌های در دسترس</h5>

    @forelse($exams as $exam)
        @php
            $status = $exam->statusLabel();
            $examAttempts = $attemptsByExam[$exam->id] ?? collect();
            $doneCount = $examAttempts->count();
            $bestScore = $doneCount > 0 ? $examAttempts->max('final_score') : null;
            $remaining = max(0, $exam->max_attempts - $doneCount);
            $inProgress = $inProgressByExam[$exam->id] ?? null;
            $canTake = $status['label'] === 'باز' && ($inProgress || $doneCount < $exam->max_attempts);

            $cardClass = 'new';
            if ($inProgress) $cardClass = 'retry';
            elseif ($doneCount >= $exam->max_attempts) $cardClass = 'done';
            elseif ($doneCount > 0) $cardClass = 'retry';
        @endphp

        <div class="card exam-card {{ $cardClass }} mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                    <div>
                        <h5 class="mb-1">{{ $exam->title }}</h5>
                        <div class="small text-muted mb-1">
                            <span class="badge bg-info">{{ $exam->classSubject?->subject?->name }}</span>
                            <span class="badge bg-secondary">{{ $exam->classSubject?->schoolClass?->name }}</span>
                            <span class="badge bg-{{ $status['color'] }}">{{ $status['label'] }}</span>
                        </div>
                    </div>
                    <div>
                        @if(!$inProgress && $doneCount === 0)
                            <span class="badge bg-success info-badge">🆕 آزمون جدید</span>
                        @elseif($inProgress)
                            <span class="badge bg-warning text-dark info-badge">⏳ در حال انجام</span>
                        @elseif($doneCount >= $exam->max_attempts)
                            <span class="badge bg-secondary info-badge">✓ تکمیل شده</span>
                        @else
                            <span class="badge bg-warning text-dark info-badge">🔄 قابل تکرار</span>
                        @endif
                    </div>
                </div>

                @if($exam->description)
                    <p class="small mb-2 text-muted">{{ $exam->description }}</p>
                @endif

                <!-- اطلاعات پایه -->
                <div class="small text-muted mb-3">
                    <span class="me-3">📝 {{ $exam->question_count }} سوال</span>
                    <span class="me-3">⏱️ {{ $exam->duration_minutes }} دقیقه</span>
                    <span class="me-3">🎯 نمره از ۲۰</span>
                    <span>🔄 {{ $exam->max_attempts }} فرصت</span>
                </div>

                @if($exam->start_at || $exam->end_at)
                    <div class="small text-muted mb-3">
                        @if($exam->start_at) شروع: {{ jdate($exam->start_at, 'yyyy/MM/dd HH:mm') }} @endif
                        @if($exam->end_at) | پایان: {{ jdate($exam->end_at, 'yyyy/MM/dd HH:mm') }} @endif
                    </div>
                @endif

                <!-- اطلاعات تلاش‌ها -->
                @if($doneCount > 0 || $inProgress)
                    <div class="bg-light rounded p-2 mb-3 small">
                        @if($inProgress)
                            <div class="text-warning mb-1">
                                ⏳ <strong>شما یک آزمون در حال انجام دارید.</strong> برای ادامه کلیک کنید.
                            </div>
                        @endif

                        @if($doneCount > 0)
                            <div class="d-flex flex-wrap gap-3">
                                <span>📊 <strong>شرکت کرده:</strong> {{ $doneCount }} از {{ $exam->max_attempts }}</span>
                                @if($bestScore !== null)
                                    <span>🏆 <strong>بهترین نمره:</strong>
                                        <span class="badge bg-{{ $bestScore >= 17 ? 'success' : ($bestScore >= 10 ? 'warning text-dark' : 'danger') }}">
                                            {{ $bestScore }} / 20
                                        </span>
                                    </span>
                                @endif
                                @if($remaining > 0)
                                    <span class="text-success">✨ <strong>{{ $remaining }} فرصت دیگر</strong> باقی مانده</span>
                                @else
                                    <span class="text-danger">❌ حداکثر تلاش انجام شده</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                <!-- دکمه عملیات -->
                <div class="text-start">
                    @if($inProgress)
                        <a href="{{ route('panel.exams.show', $inProgress) }}" class="btn btn-warning">
                            ▶ ادامه آزمون
                        </a>
                    @elseif($doneCount === 0 && $canTake)
                        <form action="{{ route('panel.exams.start', $exam) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-success"
                                    onclick="return confirm('آزمون شروع شود؟ به محض شروع، تایمر فعال می‌شود.')">
                                ▶ شروع آزمون
                            </button>
                        </form>
                    @elseif($remaining > 0 && $canTake)
                        <form action="{{ route('panel.exams.start', $exam) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-outline-primary"
                                    onclick="return confirm('این شروع تلاش شماره {{ $doneCount + 1 }} از {{ $exam->max_attempts }} است. مطمئن هستید؟')">
                                🔄 تلاش شماره {{ $doneCount + 1 }} از {{ $exam->max_attempts }}
                            </button>
                        </form>
                    @elseif($doneCount >= $exam->max_attempts)
                        <span class="badge bg-secondary">به سقف تلاش رسیده‌اید</span>
                    @else
                        <span class="badge bg-secondary">{{ $status['label'] }}</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info">در حال حاضر آزمونی در دسترس نیست.</div>
    @endforelse

    {{-- تلاش‌های قبلی --}}
    <h5 class="mt-5 mb-3">📜 تاریخچه تلاش‌های شما</h5>
    @if($pastAttempts->isEmpty())
        <p class="text-muted small">هنوز در هیچ آزمونی شرکت نکرده‌اید.</p>
    @else
        <table class="table table-sm table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>آزمون</th>
                    <th class="text-center">تلاش</th>
                    <th class="text-center">نمره</th>
                    <th class="text-center">صحیح</th>
                    <th class="text-center">تاریخ</th>
                    <th class="text-center"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($pastAttempts as $attempt)
                    <tr>
                        <td>{{ $attempt->exam?->title }}</td>
                        <td class="text-center">#{{ $attempt->attempt_number }}</td>
                        <td class="text-center">
                            @if($attempt->is_approved && $attempt->teacher_score !== null)
                                <span class="badge bg-success">{{ $attempt->teacher_score }} / 20</span>
                                <br><small class="text-success">تایید معلم</small>
                            @else
                                <span class="badge bg-{{ $attempt->score >= 10 ? 'success' : 'danger' }}">
                                    {{ $attempt->score }} / 20
                                </span>
                                <br><small class="text-muted">خودکار</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $attempt->total_correct }} / {{ $attempt->total_questions }}</td>
                        <td class="text-center"><small>{{ jdate($attempt->finished_at, 'yyyy/MM/dd HH:mm') }}</small></td>
                        <td class="text-center">
                            <a href="{{ route('panel.exams.result', $attempt) }}" class="btn btn-sm btn-outline-primary">مشاهده نتیجه</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

@endsection
