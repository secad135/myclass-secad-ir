@extends('layouts.admin')

@section('title', 'مدارس')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">مدارس</h4>
        <a href="{{ route('admin.schools.create') }}" class="btn btn-primary">➕ افزودن مدرسه</a>
    </div>

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>کد</th>
                <th>آدرس</th>
                <th>تلفن</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schools as $school)
                <tr>
                    <td>{{ $school->id }}</td>
                    <td>{{ $school->name }}</td>
                    <td>{{ $school->code }}</td>
                    <td>{{ $school->address }}</td>
                    <td>{{ $school->phone }}</td>
                    <td>
                        <a href="{{ route('admin.schools.edit', $school) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        <form action="{{ route('admin.schools.destroy', $school) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟')">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">هیچ مدرسه‌ای ثبت نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection