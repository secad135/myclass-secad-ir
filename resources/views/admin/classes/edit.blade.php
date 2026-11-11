@extends('layouts.admin')

@section('title', 'ویرایش کلاس')

@section('content')

    <h4 class="mb-3">ویرایش کلاس</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form action="{{ route('admin.classes.update', $class) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">نام کلاس</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $class->name) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">مدرسه</label>
            <select name="school_id" class="form-select">
                <option value="">— انتخاب کنید —</option>
                @foreach($schools as $school)
                    <option value="{{ $school->id }}" {{ old('school_id', $class->school_id) == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">سال تحصیلی</label>
            <select name="academic_year_id" class="form-select">
                <option value="">— انتخاب کنید —</option>
                @foreach($years as $year)
                    <option value="{{ $year->id }}" {{ old('academic_year_id', $class->academic_year_id) == $year->id ? 'selected' : '' }}>{{ $year->title }} @if($year->is_current)(جاری)@endif</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">پایه تحصیلی (اختیاری)</label>
            <select name="grade_id" class="form-select">
                <option value="">— بدون پایه —</option>
                @foreach($grades as $grade)
                    <option value="{{ $grade->id }}" {{ old('grade_id', $class->grade_id) == $grade->id ? 'selected' : '' }}>{{ $grade->name }} @if($grade->field) - {{ $grade->field }} @endif</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">معلم (اختیاری)</label>
            <select name="teacher_id" class="form-select">
                <option value="">— بدون معلم —</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ old('teacher_id', $class->teacher_id) == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }} ({{ $teacher->email }})</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-success">ذخیره تغییرات</button>
        <a href="{{ route('admin.classes.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection