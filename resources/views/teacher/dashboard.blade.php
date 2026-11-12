@extends('layouts.teacher')

@section('title', 'داشبورد معلم')

@section('head')
<style>
    .hero-banner {
        background: linear-gradient(135deg, #212529 0%, #343a40 100%);
        border-radius: 16px;
        color: white;
        padding: 25px 30px;
        margin-bottom: 24px;
    }
    .quick-action-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }
    .quick-action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important;
    }
    .action-icon {
        font-size: 2rem;
        margin-bottom: 10px;
    }
</style>
@endsection

@section('content')

    <div class="hero-banner d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1">سلام، {{ auth()->user()->name }} 👋</h3>
            <p class="mb-0 opacity-75">به پنل معلم خوش آمدید. فقط کلاس‌ها و درس‌های شما در این پنل نمایش داده می‌شود.</p>
        </div>
        <div class="text-start text-md-end">
            @if($currentYear)
                <div class="fw-bold badge bg-success text-white p-2" style="font-size: 0.9rem;">
                    📅 سال تحصیلی جاری: {{ $currentYear->title }}
                </div>
            @else
                <div class="fw-bold badge bg-danger text-white p-2">سال تحصیلی تنظیم نشده!</div>
            @endif
            <div class="opacity-75 mt-2 small">{{ jdate(now(), 'EEEE d MMMM yyyy') }}</div>
        </div>
    </div>

    @if($stats['pending'] > 0)
        <div class="alert alert-warning d-flex justify-content-between align-items-center shadow-sm mb-4">
            <div>
                <strong>⚠️ توجه:</strong> شما <strong>{{ $stats['pending'] }} پاسخ تمرین</strong> در انتظار تصحیح دارید.
            </div>
            @if(Route::has('teacher.exercises.index'))
                <a href="{{ route('teacher.exercises.index') }}" class="btn btn-sm btn-warning">بررسی تمرین‌ها</a>
            @endif
        </div>
    @endif

    <h5 class="fw-bold mb-3 text-secondary">⚡ دسترسی سریع</h5>
    <div class="row g-3 mb-4">

        <div class="col-lg-4 col-md-6">
            <div class="card quick-action-card shadow-sm">
                <div class="card-body">
                    <div class="action-icon text-primary">📅</div>
                    <h5 class="fw-bold">درس‌های من</h5>
                    <p class="text-muted small mb-3">برای ثبت حضور و غیاب، درس مورد نظر را انتخاب کنید.</p>
                    <div style="max-height: 150px; overflow-y: auto;">
                        @forelse($classSubjects as $cs)
                            @if(Route::has('teacher.attendance.show'))
                                <a href="{{ route('teacher.attendance.show', $cs) }}" class="list-group-item list-group-item-action border rounded mb-1 p-2 text-decoration-none small d-flex justify-content-between align-items-center">
                                    <span>{{ $cs->subject?->name }} - {{ $cs->schoolClass?->name }}</span>
                                    <span class="badge bg-primary">ورود →</span>
                                </a>
                            @else
                                <div class="list-group-item border rounded mb-1 p-2 small d-flex justify-content-between align-items-center">
                                    <span>{{ $cs->subject?->name }} - {{ $cs->schoolClass?->name }}</span>
                                    <span class="badge bg-secondary">به‌زودی</span>
                                </div>
                            @endif
                        @empty
                            <p class="small text-muted mb-0">هنوز درسی به شما تخصیص داده نشده است.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card quick-action-card shadow-sm">
                <div class="card-body">
                    <div class="action-icon text-success">📝</div>
                    <h5 class="fw-bold">آزمون‌های در جریان</h5>
                    <p class="text-muted small mb-3">آزمون‌های فعال درس‌های شما.</p>

                    @forelse($recentExams as $exam)
                        @if(Route::has('teacher.exams.results'))
                            <a href="{{ route('teacher.exams.results', $exam) }}" class="list-group-item list-group-item-action border rounded mb-1 p-2 text-decoration-none small d-flex justify-content-between align-items-center">
                                <span>{{ $exam->title }}</span>
                                <span class="badge bg-info text-dark">نتایج</span>
                            </a>
                        @else
                            <div class="list-group-item border rounded mb-1 p-2 small d-flex justify-content-between align-items-center">
                                <span>{{ $exam->title }}</span>
                                <span class="badge bg-secondary">به‌زودی</span>
                            </div>
                        @endif
                    @empty
                        <p class="small text-muted mb-0">آزمونی وجود ندارد.</p>
                    @endforelse

                    @if(Route::has('teacher.exams.index'))
                        <div class="mt-3">
                            <a href="{{ route('teacher.exams.index') }}" class="btn btn-sm btn-outline-success w-100">مدیریت آزمون‌های من</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="card quick-action-card shadow-sm">
                <div class="card-body">
                    <div class="action-icon text-warning">📚</div>
                    <h5 class="fw-bold">تمرین‌های اخیر</h5>
                    <p class="text-muted small mb-3">تمرین‌های فعال درس‌های شما.</p>

                    @forelse($recentExercises as $exercise)
                        @if(Route::has('teacher.exercises.submissions'))
                            <a href="{{ route('teacher.exercises.submissions', $exercise) }}" class="list-group-item list-group-item-action border rounded mb-1 p-2 text-decoration-none small d-flex justify-content-between align-items-center">
                                <span>{{ $exercise->title }}</span>
                                <span class="badge bg-warning text-dark">پاسخ‌ها</span>
                            </a>
                        @else
                            <div class="list-group-item border rounded mb-1 p-2 small d-flex justify-content-between align-items-center">
                                <span>{{ $exercise->title }}</span>
                                <span class="badge bg-secondary">به‌زودی</span>
                            </div>
                        @endif
                    @empty
                        <p class="small text-muted mb-0">تمرینی وجود ندارد.</p>
                    @endforelse

                    @if(Route::has('teacher.exercises.index'))
                        <div class="mt-3">
                            <a href="{{ route('teacher.exercises.index') }}" class="btn btn-sm btn-outline-warning w-100">مدیریت تمرین‌های من</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold mb-3 text-secondary mt-4">📊 آمار من</h5>
    <div class="row g-3">
        <div class="col-md-3 col-6">
            <div class="card text-center shadow-sm h-100 border-0 border-bottom border-primary border-4">
                <div class="card-body p-3">
                    <h2 class="fw-bold mb-1 text-dark">{{ $stats['classes'] }}</h2>
                    <small class="text-muted">کلاس</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-center shadow-sm h-100 border-0 border-bottom border-success border-4">
                <div class="card-body p-3">
                    <h2 class="fw-bold mb-1 text-dark">{{ $stats['class_subjects'] }}</h2>
                    <small class="text-muted">درس</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-center shadow-sm h-100 border-0 border-bottom border-info border-4">
                <div class="card-body p-3">
                    <h2 class="fw-bold mb-1 text-dark">{{ $stats['students'] }}</h2>
                    <small class="text-muted">دانش‌آموز</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-center shadow-sm h-100 border-0 border-bottom border-warning border-4">
                <div class="card-body p-3">
                    <h2 class="fw-bold mb-1 text-dark">{{ $stats['pending'] }}</h2>
                    <small class="text-muted">در انتظار تصحیح</small>
                </div>
            </div>
        </div>
    </div>

@endsection
