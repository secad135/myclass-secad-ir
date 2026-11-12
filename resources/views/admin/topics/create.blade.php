@php
$isTeacher = auth()->user()->is_teacher;
$panelPrefix = $isTeacher ? 'teacher' : 'admin';
$layout = $isTeacher ? 'layouts.teacher' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', 'افزودن مبحث')

@section('content')

    <h4 class="mb-3">افزودن مبحث</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form action="{{ route($panelPrefix . '.topics.store') }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        <div class="mb-3">
            <label class="form-label">درس</label>
            <select name="subject_id" class="form-select" required>
                <option value="">— انتخاب کنید —</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}" @selected(old('subject_id', $selectedSubject) == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">نام مبحث</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="مثلاً حلقه for" autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">توضیح (اختیاری)</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
        </div>
        <button class="btn btn-success">ذخیره</button>
        <a href="{{ route($panelPrefix . '.topics.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection
