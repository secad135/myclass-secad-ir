@extends('layouts.teacher')

@section('title', 'ویرایش وزن مؤلفه')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">ویرایش وزن «{{ $components[$rankingWeight->component] ?? $rankingWeight->component }}»</h4>
        <a href="{{ route('teacher.ranking-weights.index') }}" class="btn btn-outline-secondary">بازگشت</a>
    </div>

    <div class="card bg-white">
        <div class="card-body">
            <form action="{{ route('teacher.ranking-weights.update', $rankingWeight) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">مؤلفه</label>
                    <input type="text" class="form-control" value="{{ $components[$rankingWeight->component] ?? $rankingWeight->component }}" disabled>
                </div>

                <div class="mb-3">
                    <label for="weight" class="form-label">وزن (درصد)</label>
                    <input type="number" step="0.01" min="0" max="100" name="weight" id="weight"
                        value="{{ old('weight', number_format((float) $rankingWeight->weight, 2, '.', '')) }}"
                        class="form-control @error('weight') is-invalid @enderror" required>
                    @error('weight')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        وزن پیش‌فرض این مؤلفه: {{ number_format($defaults[$rankingWeight->component] ?? 0, 2) }}٪
                    </div>
                </div>

                <button type="submit" class="btn btn-success">ذخیره تغییرات</button>
                <a href="{{ route('teacher.ranking-weights.index') }}" class="btn btn-secondary">انصراف</a>
            </form>
        </div>
    </div>

@endsection
