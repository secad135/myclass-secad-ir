@extends('layouts.admin')

@section('title', 'ویرایش مدرسه')

@section('content')

    <h4 class="mb-3">ویرایش مدرسه</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('admin.schools.update', $school) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">نام مدرسه</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $school->name) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">کد مدرسه</label>
            <input type="text" name="code" class="form-control" value="{{ old('code', $school->code) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">آدرس</label>
            <input type="text" name="address" class="form-control" value="{{ old('address', $school->address) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">تلفن</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $school->phone) }}">
        </div>
        <button class="btn btn-success">ذخیره تغییرات</button>
        <a href="{{ route('admin.schools.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection