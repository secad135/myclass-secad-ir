@extends('layouts.admin')

@section('title', 'دروس')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">دروس</h4>
        <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary">➕ افزودن درس</a>
    </div>

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>کد</th>
                <th>نوع</th>
                <th>جلسه در روز</th>
                <th>روزهای هفته</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subjects as $subject)
                <tr>
                    <td>{{ $subject->id }}</td>
                    <td>{{ $subject->name }}</td>
                    <td>{{ $subject->code }}</td>
                    <td>
                        @if($subject->type === 'workshop')
                            <span class="badge bg-info">کارگاه</span>
                        @else
                            <span class="badge bg-secondary">عادی</span>
                        @endif
                    </td>
                    <td>{{ $subject->sessions_per_day }}</td>
                    <td>{{ $subject->daysLabel() }}</td>
                    <td>
                        <a href="{{ route('admin.subjects.edit', $subject) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        <form action="{{ route('admin.subjects.destroy', $subject) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟')">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">هیچ درسی ثبت نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection