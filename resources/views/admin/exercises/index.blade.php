@extends('layouts.admin')

@section('title', 'تمرین‌ها')

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
        <h4 class="mb-0">تمرین‌ها</h4>
        <a href="{{ route('admin.exercises.create') }}" class="btn btn-primary">➕ ساخت تمرین</a>
    </div>

    <table class="table table-bordered align-middle bg-white mobile-cards">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>عنوان</th>
                <th>کلاس / درس</th>
                <th class="text-center">ددلاین</th>
                <th class="text-center">حداکثر نمره</th>
                <th class="text-center">پاسخ‌ها</th>
                <th class="text-center">وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($exercises as $exercise)
                @php $status = $exercise->statusLabel(); @endphp
                <tr>
                    <td data-label="#">{{ $exercise->id }}</td>
                    <td data-label="عنوان"><strong>{{ $exercise->title }}</strong></td>
                    <td data-label="کلاس / درس">
                        <small>{{ $exercise->classSubject?->subject?->name }}</small>
                        <br><small class="text-muted">{{ $exercise->classSubject?->schoolClass?->name }}</small>
                    </td>
                    <td data-label="ددلاین" class="text-center small">
                        {{ $exercise->deadline_at ? jdate($exercise->deadline_at, 'yyyy/MM/dd HH:mm') : '—' }}
                    </td>
                    <td data-label="حداکثر نمره" class="text-center">{{ $exercise->max_score }}</td>
                    <td data-label="پاسخ‌ها" class="text-center">
                        <span class="badge bg-info">{{ $exercise->submissions_count }} پاسخ</span>
                    </td>
                    <td data-label="وضعیت" class="text-center">
                        <span class="badge bg-{{ $status['color'] }}">{{ $status['label'] }}</span>
                    </td>
                    <td data-label="عملیات">
                        <a href="{{ route('admin.exercises.submissions', $exercise) }}" class="btn btn-sm btn-info">📋 پاسخ‌ها</a>
                        <a href="{{ route('admin.exercises.edit', $exercise) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        <form action="{{ route('admin.exercises.destroy', $exercise) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟ همه پاسخ‌ها هم حذف می‌شوند.')">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">هیچ تمرینی ساخته نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $exercises->links() }}

@endsection
