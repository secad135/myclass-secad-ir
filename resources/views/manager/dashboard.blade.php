@extends('layouts.manager')

@section('title', 'داشبورد')

@section('content')
    <h4 class="mb-3">کلاس‌های مدرسه شما</h4>
    <div class="row g-3">
        @forelse($classes as $class)
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $class->name }}</h5>
                        <h6 class="card-subtitle mb-2 text-muted">{{ $class->grade?->name }} | {{ $class->academicYear?->title }}</h6>
                        <p class="card-text small">معلم: {{ $class->teacher?->name ?? '—' }}<br>تعداد دانش‌آموز: {{ $class->students_count }}</p>
                        <a href="{{ route('manager.classes.report', $class) }}" class="btn btn-success w-100 mb-2">📊 مشاهده گزارش کلاس</a>
                        <a href="{{ route('manager.classes.full-report', $class) }}" class="btn btn-outline-success w-100" target="_blank">📄 گزارش کامل چاپی</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">هیچ کلاسی برای مدرسه شما تعریف نشده است.</div></div>
        @endforelse
    </div>
@endsection
