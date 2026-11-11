@extends('layouts.admin')

@section('title', 'ویرایش تمرین')

@section('content')

    <h4 class="mb-3">ویرایش: {{ $exercise->title }}</h4>

    <form action="{{ route('admin.exercises.update', $exercise) }}" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')
        @include('admin.exercises._form')
        <button class="btn btn-success mt-3">ذخیره تغییرات</button>
        <a href="{{ route('admin.exercises.index') }}" class="btn btn-secondary mt-3">انصراف</a>
    </form>

@endsection
