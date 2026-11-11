@extends('layouts.admin')

@section('title', 'افزودن درس')

@section('content')

    <h4 class="mb-3">افزودن درس</h4>

    <form action="{{ route('admin.subjects.store') }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @include('admin.subjects._form', ['subject' => null])
        <button class="btn btn-success">ذخیره</button>
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection