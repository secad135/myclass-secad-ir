@extends('layouts.admin')

@section('title', 'افزودن دسته امتیاز')

@section('content')

    <h4 class="mb-3">افزودن دسته امتیاز</h4>

    <form action="{{ route('admin.point-categories.store') }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @include('admin.point-categories._form', ['pointCategory' => null])
        <button class="btn btn-success">ذخیره</button>
        <a href="{{ route('admin.point-categories.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection