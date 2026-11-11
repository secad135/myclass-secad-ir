@extends('layouts.admin')

@section('title', 'ویرایش سوال')

@section('content')

    <h4 class="mb-3">ویرایش سوال</h4>

    <form action="{{ route('admin.questions.update', $question) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')
        @include('admin.questions._form', ['question' => $question, 'selectedTopic' => null, 'topics' => $topics])
        <button class="btn btn-success mt-3">ذخیره تغییرات</button>
        <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary mt-3">انصراف</a>
    </form>

@endsection
