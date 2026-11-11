@extends('layouts.admin')

@section('title', 'ویرایش درس')

@section('content')

    <h4 class="mb-3">ویرایش درس</h4>

    <form action="{{ route('admin.subjects.update', $subject) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')
        @include('admin.subjects._form', ['subject' => $subject])
        <button class="btn btn-success">ذخیره تغییرات</button>
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection