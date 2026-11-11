@extends('layouts.admin')

@section('title', 'افزودن سال تحصیلی')

@section('content')

    <h4 class="mb-3">افزودن سال تحصیلی</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form action="{{ route('admin.academic-years.store') }}" method="POST" class="bg-white p-4 rounded">
        @csrf
        <div class="mb-3">
            <label class="form-label">عنوان (مثلاً 1403-1404)</label>
            <input type="text" name="title" class="form-control" value="{{ old('title') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">تاریخ شروع (شمسی)</label>
            <input type="text" name="start_date" class="form-control" value="{{ old('start_date') }}" placeholder="1403/07/01" dir="ltr" data-jdp data-jdp-only-date>
        </div>
        <div class="mb-3">
            <label class="form-label">تاریخ پایان (شمسی)</label>
            <input type="text" name="end_date" class="form-control" value="{{ old('end_date') }}" placeholder="1404/03/31" dir="ltr" data-jdp data-jdp-only-date>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_current" value="1" class="form-check-input" id="is_current" {{ old('is_current') ? 'checked' : '' }}>
            <label class="form-check-label" for="is_current">این سال، سال تحصیلی جاری است</label>
        </div>
        <button class="btn btn-success">ذخیره</button>
        <a href="{{ route('admin.academic-years.index') }}" class="btn btn-secondary">انصراف</a>
    </form>

@endsection