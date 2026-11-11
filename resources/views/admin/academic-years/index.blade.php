@extends('layouts.admin')

@section('title', 'سال‌های تحصیلی')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">سال‌های تحصیلی</h4>
        <a href="{{ route('admin.academic-years.create') }}" class="btn btn-primary">➕ افزودن سال تحصیلی</a>
    </div>

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>عنوان</th>
                <th>شروع</th>
                <th>پایان</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($years as $year)
                <tr>
                    <td>{{ $year->id }}</td>
                    <td>{{ $year->title }}</td>
                    <td>{{ jdate($year->start_date) }}</td>
                    <td>{{ jdate($year->end_date) }}</td>
                    <td>
                        @if($year->is_current)
                            <span class="badge bg-success">جاری</span>
                        @else
                            <span class="badge bg-secondary">غیرفعال</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.academic-years.edit', $year) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        <form action="{{ route('admin.academic-years.destroy', $year) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟')">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">هیچ سالی ثبت نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection