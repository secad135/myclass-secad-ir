@extends('layouts.admin')

@section('title', 'تعریف مسئول مدرسه')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">تعریف مسئول جدید برای مدرسه</h4>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">بازگشت</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <div class="alert alert-info small">
        <strong>توجه:</strong> مسئول مدرسه فقط می‌تواند گزارش‌های مدرسه خود را ببیند و هیچ دسترسی برای ویرایش، حذف یا ساخت اطلاعات ندارد.
    </div>

    <form action="{{ route('admin.managers.store') }}" method="POST" class="bg-white p-4 rounded shadow-sm">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام و نام خانوادگی مسئول</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">ایمیل (نام کاربری)</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" dir="ltr" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">رمز عبور</label>
                <input type="text" name="password" class="form-control" value="{{ old('password') }}" dir="ltr" required>
                <small class="text-muted">این رمز را به مسئول مدرسه تحویل دهید.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label">مدرسه مربوطه</label>
                <select name="school_id" class="form-select" required>
                    <option value="">— انتخاب مدرسه —</option>
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" @selected(old('school_id') == $school->id)>{{ $school->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button class="btn btn-success mt-4 px-4">ذخیره مسئول</button>
    </form>

@endsection
