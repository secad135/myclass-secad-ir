@extends('layouts.admin')

@section('title', 'ویرایش دسته امتیاز')

@section('content')

    <h4 class="mb-3">ویرایش دسته امتیاز</h4>

    <form action="{{ route('admin.point-categories.update', $pointCategory) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')
        @include('admin.point-categories._form', ['pointCategory' => $pointCategory])
        <button class="btn btn-success">ذخیره تغییرات</button>
        <a href="{{ route('admin.point-categories.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection