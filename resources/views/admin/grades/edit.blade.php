@extends('layouts.admin')

@section('title', 'ویرایش پایه')

@section('content')

    <h4 class="mb-3">ویرایش پایه تحصیلی</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form action="{{ route('admin.grades.update', $grade) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">نام پایه</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $grade->name) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">رشته</label>
            <input type="text" name="field" class="form-control" value="{{ old('field', $grade->field) }}">
        </div>
        <button class="btn btn-success">ذخیره تغییرات</button>
        <a href="{{ route('admin.grades.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection