@extends('layouts.admin')

@section('title', 'ویرایش آزمون')

@section('content')

    <h4 class="mb-3">ویرایش آزمون: {{ $exam->title }}</h4>

    <form action="{{ route('admin.exams.update', $exam) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')
        @include('admin.exams._form')
        <button class="btn btn-success mt-3">ذخیره تغییرات</button>
        <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary mt-3">انصراف</a>
    </form>

@endsection
