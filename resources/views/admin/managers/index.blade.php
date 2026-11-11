@extends('layouts.admin')

@section('title', 'مسئولین مدارس')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">مسئولین مدارس</h4>
        <a href="{{ route('admin.managers.create') }}" class="btn btn-primary">➕ تعریف مسئول جدید</a>
    </div>

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>ایمیل</th>
                <th>مدرسه</th>
                <th>رمز اولیه</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($managers as $manager)
                <tr>
                    <td>{{ $manager->id }}</td>
                    <td>{{ $manager->name }}</td>
                    <td dir="ltr"><code>{{ $manager->email }}</code></td>
                    <td>{{ $manager->school?->name ?? '—' }}</td>
                    <td>
                        @if($manager->initial_password)
                            <code class="text-success">{{ $manager->initial_password }}</code>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.managers.edit', $manager) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        <form action="{{ route('admin.managers.reset-password', $manager) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('رمز جدید تولید شود؟')">
                            @csrf
                            <button class="btn btn-sm btn-info">🔄 بازنشانی رمز</button>
                        </form>
                        <form action="{{ route('admin.managers.destroy', $manager) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('حذف شود؟')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">هیچ مسئولی تعریف نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection
