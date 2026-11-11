@extends('layouts.admin')

@section('title', 'ویرایش مبحث')

@section('content')

    <h4 class="mb-3">ویرایش مبحث</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form action="{{ route('admin.topics.update', $topic) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">درس</label>
            <select name="subject_id" class="form-select" required>
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}" @selected(old('subject_id', $topic->subject_id) == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">نام مبحث</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $topic->name) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">توضیح</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description', $topic->description) }}</textarea>
        </div>
        <button class="btn btn-success">ذخیره تغییرات</button>
        <a href="{{ route('admin.topics.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection