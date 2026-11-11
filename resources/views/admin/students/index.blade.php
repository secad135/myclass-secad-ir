@extends('layouts.admin')

@section('title', 'دانش‌آموزان')

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
        .d-flex.justify-content-between .d-flex.gap-2 {
            flex-direction: column;
            width: 100%;
        }
        .d-flex.justify-content-between .d-flex.gap-2 .btn {
            width: 100%;
            min-height: 44px;
            font-size: 0.95rem;
        }

        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
        .table {
            font-size: 0.85rem;
            min-width: 600px;
        }
        .table th,
        .table td {
            padding: 8px 5px !important;
            white-space: nowrap;
        }
        .table td small {
            font-size: 0.72rem;
            word-break: break-all;
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
        <h4 class="mb-0">دانش‌آموزان</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.students.credentials') }}" class="btn btn-warning">🔑 برگه اطلاعات ورود</a>
            <a href="{{ route('admin.students.create') }}" class="btn btn-primary">➕ افزودن دانش‌آموز</a>
        </div>
    </div>

    <table class="table table-bordered align-middle bg-white mobile-cards">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>ایمیل</th>
                <th>تعداد کلاس</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr>
                    <td data-label="#">{{ $student->id }}</td>
                    <td data-label="نام">{{ $student->name }}</td>
                    <td data-label="ایمیل" dir="ltr"><small>{{ $student->email }}</small></td>
                    <td data-label="تعداد کلاس">{{ $student->enrollments_count }}</td>
                    <td data-label="عملیات">
                        <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-info">وضعیت کلی</a>
                        <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        <form action="{{ route('admin.students.destroy', $student) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟')">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">هیچ دانش‌آموزی ثبت نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection
