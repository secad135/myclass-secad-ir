@extends('layouts.admin')

@section('title', 'افزودن دانش‌آموز')

@section('content')

    <h4 class="mb-3">افزودن دانش‌آموز</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <div class="alert alert-info small">
        ایمیل و رمز اولیه به‌طور خودکار تولید می‌شوند. بعد از ذخیره، از صفحه «برگه اطلاعات ورود» می‌توانید ببینید.
    </div>

    <form action="{{ route('admin.students.store') }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        <div class="mb-3">
            <label class="form-label">نام و نام خانوادگی</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" autofocus>
        </div>
        <button class="btn btn-success">ذخیره و تولید اطلاعات ورود</button>
        <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection