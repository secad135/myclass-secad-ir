@php
$isTeacher = auth()->user()->is_teacher;
$panelPrefix = $isTeacher ? 'teacher' : 'admin';
$layout = $isTeacher ? 'layouts.teacher' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', 'ساخت آزمون')

@section('content')

    <h4 class="mb-3">ساخت آزمون جدید</h4>

    <form action="{{ route($panelPrefix . '.exams.store') }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @include('admin.exams._form')
        <button class="btn btn-success mt-3">ذخیره آزمون</button>
        <a href="{{ route($panelPrefix . '.exams.index') }}" class="btn btn-secondary mt-3">انصراف</a>
    </form>

@endsection
