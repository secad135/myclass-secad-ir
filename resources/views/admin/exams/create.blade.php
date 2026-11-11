@extends('layouts.admin')

@section('title', 'ساخت آزمون')

@section('content')

    <h4 class="mb-3">ساخت آزمون جدید</h4>

    <form action="{{ route('admin.exams.store') }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @include('admin.exams._form')
        <button class="btn btn-success mt-3">ذخیره آزمون</button>
        <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary mt-3">انصراف</a>
    </form>

@endsection
