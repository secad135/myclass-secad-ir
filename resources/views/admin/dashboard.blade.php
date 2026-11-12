@extends('layouts.admin')

@section('title', 'داشبورد مدیریت')

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
    .list-group-item-action:hover {
        background-color: #f8f9fa;
        border-right: 3px solid #0d6efd;
    }
</style>
@endsection

@section('content')

    <!-- بنر بالای صفحه -->
    <div class="hero-banner d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1">سلام، {{ auth()->user()->name }} 👋</h3>
            <p class="mb-0 opacity-75">به اتاق فرمان سامانه خوش آمدید.</p>
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

    <!-- هشدار پاسخ‌های در انتظار تصحیح -->
    @if($pendingSubmissionsCount > 0)
        <div class="alert alert-warning d-flex justify-content-between align-items-center shadow-sm mb-4">
            <div>
                <strong>⚠️ توجه:</strong> شما <strong>{{ $pendingSubmissionsCount }} پاسخ تمرین</strong> در انتظار تصحیح دارید.
            </div>
            <a href="{{ route('admin.exercises.index') }}" class="btn btn-sm btn-warning">بررسی تمرین‌ها</a>
        </div>
    @endif

    <!-- بخش ۱: دسترسی سریع (اولویت اصلی) -->
    <h5 class="fw-bold mb-3 text-secondary">⚡ دسترسی سریع</h5>
    <div class="row g-3 mb-4">
        
        <!-- کارت ثبت حضور و غیاب -->
        <div class="col-lg-4 col-md-6">
            <div class="card quick-action-card shadow-sm">
                <div class="card-body">
                    <div class="action-icon text-primary">📅</div>
                    <h5 class="fw-bold">ثبت حضور و غیاب</h5>
                    <p class="text-muted small mb-3">کلاس مورد نظر را انتخاب کنید تا وارد صفحه ثبت شوید.</p>
                    <div style="max-height: 150px; overflow-y: auto;">
                        @forelse($classSubjects as $cs)
                            <a href="{{ route('admin.attendance.show', $cs) }}" class="list-group-item list-group-item-action border rounded mb-1 p-2 text-decoration-none small d-flex justify-content-between align-items-center">
                                <span>{{ $cs->subject?->name }} - {{ $cs->schoolClass?->name }}</span>
                                <span class="badge bg-primary">ورود →</span>
                            </a>
                        @empty
                            <p class="small text-muted mb-0">کلاسی تعریف نشده است.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- کارت آزمون‌ها -->
        <div class="col-lg-4 col-md-6">
            <div class="card quick-action-card shadow-sm">
                <div class="card-body">
                    <div class="action-icon text-success">📝</div>
                    <h5 class="fw-bold">آزمون‌های در جریان</h5>
                    <p class="text-muted small mb-3">آزمون‌های فعال و نتایج اخیر.</p>
                    
                    @forelse($recentExams as $exam)
                        <a href="{{ route('admin.exams.results', $exam) }}" class="list-group-item list-group-item-action border rounded mb-1 p-2 text-decoration-none small d-flex justify-content-between align-items-center">
                            <span>{{ $exam->title }}</span>
                            <span class="badge bg-info text-dark">نتایج</span>
                        </a>
                    @empty
                        <p class="small text-muted mb-0">آزمونی وجود ندارد.</p>
                    @endforelse
                    
                    <div class="mt-3">
                        <a href="{{ route('admin.exams.index') }}" class="btn btn-sm btn-outline-success w-100">مدیریت تمام آزمون‌ها</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- کارت تمرین‌ها -->
        <div class="col-lg-4 col-md-12">
            <div class="card quick-action-card shadow-sm">
                <div class="card-body">
                    <div class="action-icon text-warning">📚</div>
                    <h5 class="fw-bold">تمرین‌های اخیر</h5>
                    <p class="text-muted small mb-3">تمرین‌های فعال و پاسخ‌های ارسالی.</p>
                    
                    @forelse($recentExercises as $exercise)
                        <a href="{{ route('admin.exercises.submissions', $exercise) }}" class="list-group-item list-group-item-action border rounded mb-1 p-2 text-decoration-none small d-flex justify-content-between align-items-center">
                            <span>{{ $exercise->title }}</span>
                            <span class="badge bg-warning text-dark">پاسخ‌ها</span>
                        </a>
                    @empty
                        <p class="small text-muted mb-0">تمرینی وجود ندارد.</p>
                    @endforelse
                    
                    <div class="mt-3">
                        <a href="{{ route('admin.exercises.index') }}" class="btn btn-sm btn-outline-warning w-100">مدیریت تمام تمرین‌ها</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش ۲: آمار کلی -->
    <h5 class="fw-bold mb-3 text-secondary mt-4">📊 آمار سامانه</h5>
    <div class="row g-3">
        <div class="col-md-3 col-6">
            <a href="{{ route('admin.students.index') }}" class="text-decoration-none">
                <div class="card text-center shadow-sm h-100 border-0 border-bottom border-primary border-4">
                    <div class="card-body p-3">
                        <h2 class="fw-bold mb-1 text-dark">{{ $stats['students'] }}</h2>
                        <small class="text-muted">دانش‌آموز</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="{{ route('admin.classes.index') }}" class="text-decoration-none">
                <div class="card text-center shadow-sm h-100 border-0 border-bottom border-success border-4">
                    <div class="card-body p-3">
                        <h2 class="fw-bold mb-1 text-dark">{{ $stats['classes'] }}</h2>
                        <small class="text-muted">کلاس</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="{{ route('admin.schools.index') }}" class="text-decoration-none">
                <div class="card text-center shadow-sm h-100 border-0 border-bottom border-info border-4">
                    <div class="card-body p-3">
                        <h2 class="fw-bold mb-1 text-dark">{{ $stats['schools'] }}</h2>
                        <small class="text-muted">مدرسه</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="{{ route('admin.academic-years.index') }}" class="text-decoration-none">
                <div class="card text-center shadow-sm h-100 border-0 border-bottom border-warning border-4">
                    <div class="card-body p-3">
                        <h2 class="fw-bold mb-1 text-dark">{{ $stats['years'] }}</h2>
                        <small class="text-muted">سال تحصیلی</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- آخرین فعالیت دانش‌آموزان --}}
    <h5 class="fw-bold mb-3 text-secondary mt-4">📊 آخرین فعالیت دانش‌آموزان (۷ روز اخیر)</h5>

    @if($recentActivities->isEmpty())
        <div class="alert alert-light border text-center py-3">
            <p class="text-muted mb-0 small">در ۷ روز اخیر هیچ فعالیتی از دانش‌آموزان ثبت نشده است.</p>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 180px;">دانش‌آموز</th>
                            <th>فعالیت</th>
                            <th style="width: 150px;">زمان</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentActivities as $i => $log)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ $log->user?->name ?? 'کاربر حذف شده' }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->action === 'exam_submit' ? 'success' : ($log->action === 'exercise_submit' ? 'primary' : ($log->action === 'password_change' ? 'warning text-dark' : 'secondary')) }} me-1">
                                        {{ $log->description }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    @if($log->created_at->isToday())
                                        امروز {{ jdate($log->created_at, 'HH:mm') }}
                                    @elseif($log->created_at->isYesterday())
                                        دیروز {{ jdate($log->created_at, 'HH:mm') }}
                                    @else
                                        {{ jdate($log->created_at, 'yyyy/MM/dd HH:mm') }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection