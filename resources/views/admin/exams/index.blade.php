@php
$isTeacher = auth()->user()->is_teacher;
$panelPrefix = $isTeacher ? 'teacher' : 'admin';
$layout = $isTeacher ? 'layouts.teacher' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', $isTeacher ? 'آزمون‌های من' : 'آزمون‌ها')

@section('head')
<style>
    @media (max-width: 991.98px) {
        .d-flex.justify-content-between.mb-3 {
            flex-direction: column;
            gap: 10px;
            align-items: stretch !important;
        }
        .d-flex.justify-content-between h4 {
            font-size: 1.05rem;
        }
        .d-flex.justify-content-between .btn {
            width: 100%;
            min-height: 44px;
        }

        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
        .table {
            font-size: 0.82rem;
            min-width: 700px;
        }
        .table th,
        .table td {
            padding: 8px 5px !important;
            white-space: nowrap;
        }
        .table td small {
            font-size: 0.72rem;
            white-space: normal;
            word-break: break-word;
        }
        .table .btn-sm {
            font-size: 0.75rem;
            padding: 5px 8px;
        }
        .badge { font-size: 0.72rem !important; }
    }
</style>
@endsection

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ $isTeacher ? 'آزمون‌های من' : 'آزمون‌ها' }}</h4>
        <a href="{{ route($panelPrefix . '.exams.create') }}" class="btn btn-primary">➕ ساخت آزمون جدید</a>
    </div>

    <table class="table table-bordered align-middle bg-white mobile-cards">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>عنوان</th>
                <th>کلاس / درس</th>
                <th class="text-center">تعداد سوال</th>
                <th class="text-center">مدت</th>
                <th class="text-center">بازه</th>
                <th class="text-center">تلاش‌ها</th>
                <th class="text-center">وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($exams as $exam)
                @php $status = $exam->statusLabel(); @endphp
                <tr>
                    <td data-label="#">{{ $exam->id }}</td>
                    <td data-label="عنوان"><strong>{{ $exam->title }}</strong></td>
                    <td data-label="کلاس / درس">
                        <small>{{ $exam->classSubject?->subject?->name }}</small>
                        <br><small class="text-muted">{{ $exam->classSubject?->schoolClass?->name }}</small>
                    </td>
                    <td data-label="تعداد سوال" class="text-center">{{ $exam->question_count }}</td>
                    <td data-label="مدت" class="text-center">{{ $exam->duration_minutes }} دقیقه</td>
                    <td data-label="بازه" class="text-center small">
                        @if($exam->start_at)
                            {{ jdate($exam->start_at, 'yyyy/MM/dd HH:mm') }}
                        @else
                            —
                        @endif
                        @if($exam->end_at)
                            <br>تا {{ jdate($exam->end_at, 'yyyy/MM/dd HH:mm') }}
                        @endif
                    </td>
                    <td data-label="تلاش‌ها" class="text-center">{{ $exam->attempts_count }}</td>
                    <td data-label="وضعیت" class="text-center">
                        <span class="badge bg-{{ $status['color'] }}">{{ $status['label'] }}</span>
                    </td>
                    <td data-label="عملیات">
                        <a href="{{ route($panelPrefix . '.exams.results', $exam) }}" class="btn btn-sm btn-info">📊 نتایج</a>
                        <a href="{{ route($panelPrefix . '.exams.edit', $exam) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        <form action="{{ route($panelPrefix . '.exams.destroy', $exam) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger"
                                    onclick="return confirm('حذف آزمون؟ تمام تلاش‌ها و پاسخ‌ها هم حذف می‌شوند.')">
                                حذف
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted">هیچ آزمونی ساخته نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $exams->links() }}

@endsection
