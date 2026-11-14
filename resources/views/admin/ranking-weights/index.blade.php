@extends('layouts.admin')

@section('title', 'وزن‌های رتبه‌بندی')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">وزن مؤلفه‌های نمره رتبه</h4>
            <div class="text-muted small">
                برای هر معلم می‌توانید وزن مؤلفه‌های آزمون، تمرین، حضور و امتیاز دستی را جداگانه تنظیم کنید.
            </div>
        </div>
    </div>

    <div class="card bg-white mb-3">
        <div class="card-body">
            <div class="fw-semibold mb-2">وزن‌های پیش‌فرض سیستم</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($defaults as $component => $percent)
                    <span class="badge bg-secondary fs-6">
                        {{ \App\Models\RankingWeight::label($component) }}: {{ number_format($percent, 2) }}٪
                    </span>
                @endforeach
            </div>
            <div class="small text-muted mt-2">
                معلمانی که وزن سفارشی ندارند از همین مقادیر استفاده می‌کنند.
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>معلم</th>
                    <th>ایمیل</th>
                    <th class="text-center">مجموع وزن مؤثر</th>
                    <th class="text-center">تعداد وزن سفارشی</th>
                    <th class="text-center">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teachers as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="fw-semibold">{{ $row['teacher']->name }}</td>
                        <td class="text-muted">{{ $row['teacher']->email }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ abs($row['total'] - 100) < 0.01 ? 'success' : 'warning text-dark' }}">
                                {{ number_format($row['total'], 2) }}٪
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info text-dark">{{ $row['override_count'] }} از ۴</span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.ranking-weights.show', $row['teacher']) }}"
                                class="btn btn-sm btn-outline-primary">تنظیم وزن‌ها</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">معلمی ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection
