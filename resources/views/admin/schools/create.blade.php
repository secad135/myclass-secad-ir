@extends('layouts.admin')

@section('title', 'افزودن مدرسه')

@section('content')

    <h4 class="mb-3">افزودن مدرسه</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('admin.schools.store') }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        <div class="mb-3">
            <label class="form-label">نام مدرسه</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">کد مدرسه</label>
            <input type="text" name="code" class="form-control" value="{{ old('code') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">آدرس</label>
            <input type="text" name="address" class="form-control" value="{{ old('address') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">تلفن</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
        </div>
        <button class="btn btn-success">ذخیره</button>
        <a href="{{ route('admin.schools.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection