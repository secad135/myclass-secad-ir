@extends('layouts.admin')

@section('title', 'دسته‌های امتیاز')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">دسته‌های امتیاز</h4>
        <a href="{{ route('admin.point-categories.create') }}" class="btn btn-primary">➕ دسته جدید</a>
    </div>

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>امتیاز پیش‌فرض</th>
                <th>توضیح</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $cat)
                <tr>
                    <td>{{ $cat->id }}</td>
                    <td>{{ $cat->name }}</td>
                    <td><span class="badge bg-{{ $cat->color() }}">{{ $cat->pointsLabel() }}</span></td>
                    <td><small>{{ $cat->description }}</small></td>
                    <td>
                        @if($cat->is_active)
                            <span class="badge bg-success">فعال</span>
                        @else
                            <span class="badge bg-secondary">غیرفعال</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.point-categories.edit', $cat) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        <form action="{{ route('admin.point-categories.destroy', $cat) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟')">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">هیچ دسته‌ای ثبت نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection