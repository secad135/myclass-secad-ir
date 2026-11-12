@extends('layouts.admin')

@section('title', 'معلم‌ها')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">معلم‌ها</h4>
        <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary">➕ تعریف معلم جدید</a>
    </div>

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>ایمیل</th>
                <th class="text-center">درس‌های تدریس</th>
                <th>رمز اولیه</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teachers as $teacher)
                <tr>
                    <td>{{ $teacher->id }}</td>
                    <td>{{ $teacher->name }}</td>
                    <td dir="ltr"><code>{{ $teacher->email }}</code></td>
                    <td class="text-center">
                        <span class="badge bg-info">{{ $teacher->class_subjects_as_teacher_count }} درس</span>
                        @if($teacher->homeroom_classes_count > 0)
                            <span class="badge bg-secondary">{{ $teacher->homeroom_classes_count }} کلاس سرپرست</span>
                        @endif
                    </td>
                    <td>
                        @if($teacher->initial_password)
                            <code class="text-success">{{ $teacher->initial_password }}</code>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        <form action="{{ route('admin.teachers.reset-password', $teacher) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('رمز جدید تولید شود؟')">
                            @csrf
                            <button class="btn btn-sm btn-info">🔄 بازنشانی رمز</button>
                        </form>
                        <form action="{{ route('admin.teachers.destroy', $teacher) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('حذف شود؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">هیچ معلمی تعریف نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection
