@extends('layouts.admin')

@section('title', 'پایه‌های تحصیلی')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">پایه‌های تحصیلی</h4>
        <a href="{{ route('admin.grades.create') }}" class="btn btn-primary">➕ افزودن پایه</a>
    </div>

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام پایه</th>
                <th>رشته</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grades as $grade)
                <tr>
                    <td>{{ $grade->id }}</td>
                    <td>{{ $grade->name }}</td>
                    <td>{{ $grade->field }}</td>
                    <td>
                        <a href="{{ route('admin.grades.edit', $grade) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        <form action="{{ route('admin.grades.destroy', $grade) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟')">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">هیچ پایه‌ای ثبت نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection