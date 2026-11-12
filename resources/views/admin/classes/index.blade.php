@php
$isTeacher = auth()->user()->is_teacher;
$panelPrefix = $isTeacher ? 'teacher' : 'admin';
$layout = $isTeacher ? 'layouts.teacher' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', $isTeacher ? 'کلاس‌های من' : 'کلاس‌ها')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ $isTeacher ? 'کلاس‌های من' : 'کلاس‌ها' }}</h4>
        @if(!$isTeacher)
            <a href="{{ route('admin.classes.create') }}" class="btn btn-primary">➕ افزودن کلاس</a>
        @endif
    </div>

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام کلاس</th>
                <th>مدرسه</th>
                <th>سال تحصیلی</th>
                <th>پایه</th>
                <th>معلم</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($classes as $class)
                <tr>
                    <td>{{ $class->id }}</td>
                    <td>{{ $class->name }}</td>
                    <td>{{ $class->school?->name ?? '—' }}</td>
                    <td>
                        {{ $class->academicYear?->title ?? '—' }}
                        @if($class->academicYear?->is_current)
                            <span class="badge bg-success">جاری</span>
                        @endif
                    </td>
                    <td>{{ $class->grade?->name ?? '—' }}</td>
                    <td>{{ $class->teacher?->name ?? '—' }}</td>
                    <td>
                        <a href="{{ route($panelPrefix . '.classes.show', $class) }}" class="btn btn-sm btn-info">مشاهده</a>
                        @if(Route::has($panelPrefix . '.classes.report'))
                            <a href="{{ route($panelPrefix . '.classes.report', $class) }}" class="btn btn-sm btn-primary">📊 گزارش</a>
                        @endif
                        @if(!$isTeacher)
                            <a href="{{ route('admin.classes.edit', $class) }}" class="btn btn-sm btn-warning">ویرایش</a>
                            <form action="{{ route('admin.classes.destroy', $class) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟')">حذف</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">هیچ کلاسی ثبت نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection