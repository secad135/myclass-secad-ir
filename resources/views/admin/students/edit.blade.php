@extends('layouts.admin')

@section('title', 'ویرایش دانش‌آموز')

@section('content')

    <h4 class="mb-3">ویرایش دانش‌آموز</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <div class="alert alert-light border small">
        <div><strong>ایمیل:</strong> <code dir="ltr">{{ $student->email }}</code></div>
        <div class="mt-2">
            برای تغییر ایمیل یا رمز، از
            <a href="{{ route('admin.students.credentials') }}">برگه اطلاعات ورود</a>
            استفاده کنید.
        </div>
    </div>

    <form action="{{ route('admin.students.update', $student) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">نام و نام خانوادگی</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $student->name) }}">
        </div>
        <button class="btn btn-success">ذخیره تغییرات</button>
        <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection