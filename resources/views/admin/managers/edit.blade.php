@extends('layouts.admin')

@section('title', 'ویرایش مسئول مدرسه')

@section('content')

    <h4 class="mb-3">ویرایش مسئول: {{ $manager->name }}</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form action="{{ route('admin.managers.update', $manager) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام و نام خانوادگی</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $manager->name) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">ایمیل (نام کاربری)</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $manager->email) }}" dir="ltr" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">مدرسه مربوطه</label>
                <select name="school_id" class="form-select" required>
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" @selected(old('school_id', $manager->school_id) == $school->id)>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-success">ذخیره تغییرات</button>
            <a href="{{ route('admin.managers.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>

@endsection
