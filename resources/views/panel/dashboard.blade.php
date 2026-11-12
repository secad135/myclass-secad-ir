@extends('layouts.panel')

@section('title', 'داشبورد من')

@section('head')
<style>
    /* استایل‌های اختصاصی داشبورد */
    .hero-banner {
        background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        border-radius: 16px;
        color: white;
        padding: 30px;
        margin-bottom: 24px;
        box-shadow: 0 10px 20px rgba(13, 110, 253, 0.15);
    }
    .stat-card {
        border: none;
        border-radius: 16px;
        transition: transform 0.2s, box-shadow 0.2s;
        text-decoration: none;
        display: block;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .stat-icon {
        font-size: 2.5rem;
        opacity: 0.8;
        line-height: 1;
    }
    .gradient-points { background: linear-gradient(135deg, #198754 0%, #20c997 100%); color: white; }
    .gradient-attendance { background: linear-gradient(135deg, #0d6efd 0%, #6ea8fe 100%); color: white; }
    .gradient-rank { background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%); color: white; }
    
    .item-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        transition: all 0.2s;
        background: white;
        height: 100%;
    }
    .item-card:hover {
        border-color: #0d6efd;
        box-shadow: 0 5px 15px rgba(13, 110, 253, 0.08);
    }
</style>
@endsection

@section('content')

    <!-- بنر خوش‌آمدگویی -->
    <div class="hero-banner d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1">سلام، {{ $student->name }} 👋</h3>
            <p class="mb-0 opacity-75">به پنل کاربری خود خوش آمدید. امروز آماده یادگیری هستید؟</p>
        </div>
        <div class="text-start text-md-end">
            <div class="fw-bold">{{ jdate(now(), 'EEEE') }}</div>
            <div class="opacity-75">{{ jdate(now(), 'd MMMM yyyy') }}</div>
        </div>
    </div>

    <!-- کارت‌های آماری -->
    <div class="row g-3 mb-4">
        <!-- امتیاز به تفکیک درس -->
        <div class="col-md-4">
            <a href="{{ route('panel.points') }}" class="card stat-card gradient-points shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="opacity-75 small fw-bold">امتیاز به تفکیک درس</div>
                        <div class="stat-icon">⭐</div>
                    </div>
                    @forelse($points['by_subject']->take(3) as $subjectPoint)
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span class="opacity-90">{{ $subjectPoint['subject_name'] }}</span>
                            <strong>{{ $subjectPoint['total'] > 0 ? '+' : '' }}{{ $subjectPoint['total'] }}</strong>
                        </div>
                    @empty
                        <div class="small opacity-75">هنوز امتیازی ثبت نشده است.</div>
                    @endforelse
                    <div class="border-top border-light pt-2 mt-2 d-flex justify-content-between align-items-center small">
                        <span class="opacity-75">جمع دروس</span>
                        <strong>{{ $points['total'] > 0 ? '+' : '' }}{{ $points['total'] }}</strong>
                    </div>
                    <small class="opacity-75 d-block mt-2">برای مشاهده جزئیات کلیک کنید</small>
                </div>
            </a>
        </div>

        <!-- درصد حضور -->
        <div class="col-md-4">
            <a href="{{ route('panel.attendance') }}" class="card stat-card gradient-attendance shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="opacity-75 small fw-bold mb-1">درصد حضور</div>
                        <h2 class="mb-0 fw-bold">{{ $overall['percentage'] }}٪</h2>
                        <small class="opacity-75">برای مشاهده جزئیات کلیک کنید</small>
                    </div>
                    <div class="stat-icon">📅</div>
                </div>
            </a>
        </div>

        <!-- رتبه در کلاس -->
        <div class="col-md-4">
            <div class="card stat-card gradient-rank shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="opacity-75 small fw-bold mb-1">رتبه در کلاس</div>
                        <h2 class="mb-0 fw-bold">{{ $rank }}</h2>
                        @if(isset($totalStudents) && $totalStudents > 0)
                            <small class="opacity-75">از بین {{ $totalStudents }} دانش‌آموز</small>
                        @endif
                    </div>
                    <div class="stat-icon">🏆</div>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش آزمون‌ها و تمرین‌ها -->
    <div class="row g-4">
        
        <!-- آزمون‌های جدید -->
        <div class="col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">📝 آزمون‌های جدید</h5>
                <a href="{{ route('panel.exams.index') }}" class="small text-decoration-none">مشاهده همه</a>
            </div>

            @if($newExams->isEmpty())
                <div class="alert alert-light border text-center py-4">
                    <div class="fs-1 text-muted mb-2">🎉</div>
                    <p class="mb-0 text-muted">
                        @if($retakeableCount > 0)
                            آزمون جدیدی نداری، اما در {{ $retakeableCount }} آزمون می‌توانی دوباره تلاش کنی.
                        @else
                            در حال حاضر هیچ آزمون جدیدی برای شما وجود ندارد.
                        @endif
                    </p>
                    @if($retakeableCount > 0)
                        <a href="{{ route('panel.exams.index') }}" class="btn btn-sm btn-outline-primary mt-2">
                            مشاهده آزمون‌های قابل تکرار
                        </a>
                    @endif
                </div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach($newExams as $exam)
                        <div class="card item-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold mb-0 text-dark">{{ $exam->title }}</h6>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $exam->classSubject?->subject?->name }}</span>
                                </div>
                                <div class="text-muted small mb-3">
                                    <span class="me-3">⏱️ {{ $exam->duration_minutes }} دقیقه</span>
                                    <span class="me-3">📝 {{ $exam->question_count }} سوال</span>
                                    @if($exam->end_at)
                                        <span class="text-danger">تا: {{ jdate($exam->end_at, 'yyyy/MM/dd HH:mm') }}</span>
                                    @endif
                                </div>
                                <div class="mb-2">
                                    <span class="badge bg-success">🆕 آزمون جدید</span>
                                    <span class="text-muted small">شما هنوز در این آزمون شرکت نکرده‌اید</span>
                                </div>
                                <form action="{{ route('panel.exams.start', $exam) }}" method="POST" class="mt-2">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary w-100" onclick="return confirm('آزمون شروع شود؟ زمان شروع به محض کلیک ثبت می‌شود.')">
                                        ▶ شروع آزمون
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- تمرین‌های فعال -->
        <div class="col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">📚 تمرین‌های پیش‌رو</h5>
                <a href="{{ route('panel.exercises.index') }}" class="small text-decoration-none">مشاهده همه</a>
            </div>

            @if($activeExercises->isEmpty())
                <div class="alert alert-light border text-center py-4">
                    <div class="fs-1 text-muted mb-2">☕</div>
                    <p class="mb-0 text-muted">در حال حاضر هیچ تمرین فعالی برای شما وجود ندارد.</p>
                </div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach($activeExercises as $exercise)
                        <div class="card item-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold mb-0 text-dark">{{ $exercise->title }}</h6>
                                    <span class="badge bg-info bg-opacity-10 text-info">{{ $exercise->classSubject?->subject?->name }}</span>
                                </div>
                                <div class="text-muted small mb-3">
                                    <span class="me-3">🎯 نمره: {{ $exercise->max_score }}</span>
                                    @if($exercise->deadline_at)
                                        <span class="text-danger">مهلت: {{ jdate($exercise->deadline_at, 'yyyy/MM/dd HH:mm') }}</span>
                                    @else
                                        <span>بدون محدودیت زمانی</span>
                                    @endif
                                </div>
                                <a href="{{ route('panel.exercises.show', $exercise) }}" class="btn btn-sm btn-outline-primary w-100">
                                    مشاهده و پاسخ
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

@endsection
