@extends('layouts.admin')

@section('title', 'ویرایش سال تحصیلی')

@section('content')

    <h4 class="mb-3">ویرایش سال تحصیلی</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form action="{{ route('admin.academic-years.update', $academicYear) }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">عنوان</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $academicYear->title) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">تاریخ شروع (شمسی)</label>
            <input type="text" name="start_date" class="form-control" value="{{ old('start_date', $academicYear->start_date ? jdate($academicYear->start_date) : '') }}" dir="ltr" data-jdp data-jdp-only-date>
        </div>
        <div class="mb-3">
            <label class="form-label">تاریخ پایان (شمسی)</label>
            <input type="text" name="end_date" class="form-control" value="{{ old('end_date', $academicYear->end_date ? jdate($academicYear->end_date) : '') }}" dir="ltr" data-jdp data-jdp-only-date>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_current" value="1" class="form-check-input" id="is_current" {{ old('is_current', $academicYear->is_current) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_current">این سال، سال تحصیلی جاری است</label>
        </div>
        <button class="btn btn-success">ذخیره تغییرات</button>
        <a href="{{ route('admin.academic-years.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection