@extends('layouts.admin')

@section('title', 'تعریف معلم')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">تعریف معلم جدید</h4>
        <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">بازگشت</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <div class="alert alert-info small">
        <strong>توجه:</strong> رمز عبور توسط سیستم تولید می‌شود و پس از ذخیره نمایش داده می‌شود.
        معلم می‌تواند در چند مدرسه و چند کلاس درس بدهد؛ اتصال به درس‌ها از صفحه هر کلاس انجام می‌شود.
        معلم در اولین ورود باید رمز خود را تغییر دهد.
    </div>

    <form action="{{ route('admin.teachers.store') }}" method="POST" class="bg-white p-4 rounded shadow-sm">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام و نام خانوادگی معلم</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">ایمیل (نام کاربری)</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" dir="ltr" required>
            </div>
        </div>

        <button class="btn btn-success mt-4 px-4">ذخیره معلم</button>
    </form>

@endsection
