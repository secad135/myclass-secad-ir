@extends('layouts.admin')

@section('title', 'افزودن پایه')

@section('content')

    <h4 class="mb-3">افزودن پایه تحصیلی</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form action="{{ route('admin.grades.store') }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        <div class="mb-3">
            <label class="form-label">نام پایه</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">رشته</label>
            <input type="text" name="field" class="form-control" value="{{ old('field') }}">
        </div>
        <button class="btn btn-success">ذخیره</button>
        <a href="{{ route('admin.grades.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection