@extends('layouts.admin')

@section('title', 'وزن‌های رتبه‌بندی ' . $teacher->name)

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">وزن‌های رتبه‌بندی «{{ $teacher->name }}»</h4>
            <div class="text-muted small">
                وزن هر مؤلفه را بین ۰ تا ۱۰۰ درصد تنظیم کنید. این تنظیمات روی رتبه‌بندی کلاس‌های همین معلم اثر دارد.
            </div>
        </div>
        <a href="{{ route('admin.ranking-weights.index') }}" class="btn btn-outline-secondary">بازگشت</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card bg-white mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="text-muted">مجموع وزن‌های مؤثر:</span>
                <span class="badge bg-{{ abs($total - 100) < 0.01 ? 'success' : 'warning text-dark' }} fs-6">
                    {{ number_format($total, 2) }}٪
                </span>
            </div>
            <div class="small text-muted">
                اگر مجموع ۱۰۰٪ نباشد، نمره رتبه به‌صورت میانگین وزنی نرمال‌سازی می‌شود.
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>مؤلفه</th>
                    <th class="text-center">وزن مؤثر</th>
                    <th class="text-center">وزن پیش‌فرض</th>
                    <th class="text-center">وضعیت</th>
                    <th style="min-width: 260px;">تنظیم وزن</th>
                </tr>
            </thead>
            <tbody>
                @foreach($components as $component => $label)
                    @php
                        $override = $overrides->get($component);
                        $effective = $percents[$component] ?? 0;
                        $default = $defaults[$component] ?? 0;
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $label }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary fs-6">{{ number_format($effective, 2) }}٪</span>
                        </td>
                        <td class="text-center text-muted">{{ number_format($default, 2) }}٪</td>
                        <td class="text-center">
                            @if($override)
                                <span class="badge bg-info text-dark">سفارشی</span>
                            @else
                                <span class="badge bg-secondary">پیش‌فرض</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <form action="{{ route('admin.ranking-weights.update', [$teacher, $component]) }}"
                                    method="POST" class="d-flex align-items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="weight" step="0.01" min="0" max="100"
                                        value="{{ number_format((float) $effective, 2, '.', '') }}"
                                        class="form-control form-control-sm" style="width: 110px;" required>
                                    <button type="submit" class="btn btn-sm btn-success">ذخیره</button>
                                </form>
                                @if($override)
                                    <form action="{{ route('admin.ranking-weights.destroy', [$teacher, $component]) }}"
                                        method="POST"
                                        onsubmit="return confirm('وزن این مؤلفه حذف شود و به مقدار پیش‌فرض بازگردد؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">بازنشانی</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="alert alert-light border small text-muted">
        <strong>نکته:</strong>
        نمره رتبه هر دانش‌آموز از ترکیب وزنی آزمون‌ها، تمرین‌ها، حضور و غیاب و امتیاز دستی ساخته می‌شود.
        اگر داده‌ای برای یکی از مؤلفه‌ها در بازه انتخابی وجود نداشته باشد، وزن آن مؤلفه بین مؤلفه‌های موجود تقسیم می‌شود.
    </div>

@endsection
