@extends('layouts.teacher')

@section('title', 'ثبت وزن مؤلفه')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0">ثبت وزن سفارشی مؤلفه</h4>
        <a href="{{ route('teacher.ranking-weights.index') }}" class="btn btn-outline-secondary">بازگشت</a>
    </div>

    <div class="card bg-white">
        <div class="card-body">
            <form action="{{ route('teacher.ranking-weights.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="component" class="form-label">مؤلفه</label>
                    <select name="component" id="component" class="form-select @error('component') is-invalid @enderror">
                        @foreach($components as $component => $label)
                            @if(in_array($component, $availableComponents, true))
                                <option value="{{ $component }}"
                                    @selected(old('component', $selectedComponent) === $component)>
                                    {{ $label }} (پیش‌فرض {{ number_format($defaults[$component] ?? 0, 2) }}٪)
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('component')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="weight" class="form-label">وزن (درصد)</label>
                    <input type="number" step="0.01" min="0" max="100" name="weight" id="weight"
                        value="{{ old('weight', number_format($defaults[$selectedComponent] ?? 0, 2, '.', '')) }}"
                        class="form-control @error('weight') is-invalid @enderror" required>
                    @error('weight')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">مقدار بین ۰ تا ۱۰۰ درصد. مجموع وزن‌های مؤثر باید بیشتر از صفر باشد.</div>
                </div>

                <button type="submit" class="btn btn-success">ذخیره</button>
                <a href="{{ route('teacher.ranking-weights.index') }}" class="btn btn-secondary">انصراف</a>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const defaults = @json($defaults);
            const select = document.getElementById('component');
            const weight = document.getElementById('weight');

            if (!select || !weight) {
                return;
            }

            select.addEventListener('change', function () {
                if (Object.prototype.hasOwnProperty.call(defaults, select.value)) {
                    weight.value = Number(defaults[select.value]).toFixed(2);
                }
            });
        });
    </script>

@endsection
