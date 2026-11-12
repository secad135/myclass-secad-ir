@extends('layouts.admin')

@section('title', 'ویرایش معلم')

@section('content')

    <h4 class="mb-3">ویرایش معلم: {{ $teacher->name }}</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form action="{{ route('admin.teachers.update', $teacher) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام و نام خانوادگی</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $teacher->name) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">ایمیل (نام کاربری)</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $teacher->email) }}" dir="ltr" required>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-success">ذخیره تغییرات</button>
            <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>

@endsection
