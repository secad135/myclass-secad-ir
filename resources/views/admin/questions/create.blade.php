@extends('layouts.admin')

@section('title', 'افزودن سوال')

@section('content')

    <h4 class="mb-3">افزودن سوال</h4>

    <form action="{{ route('admin.questions.store') }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @include('admin.questions._form', ['question' => null, 'selectedTopic' => $selectedTopic, 'topics' => $topics])
        <button class="btn btn-success mt-3">ذخیره</button>
        <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary mt-3">انصراف</a>
    </form>

@endsection