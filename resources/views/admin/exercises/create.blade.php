@extends('layouts.admin')

@section('title', 'ساخت تمرین')

@section('content')

    <h4 class="mb-3">ساخت تمرین جدید</h4>

    <form action="{{ route('admin.exercises.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded">
        @csrf
        @include('admin.exercises._form')
        <button class="btn btn-success mt-3">ذخیره</button>
        <a href="{{ route('admin.exercises.index') }}" class="btn btn-secondary mt-3">انصراف</a>
    </form>

@endsection
