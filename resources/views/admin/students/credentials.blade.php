@extends('layouts.admin')

@section('title', 'برگه اطلاعات ورود')

@section('head')
<style>
    @media (max-width: 991.98px) {
        .d-flex.justify-content-between.mb-3 {
            flex-direction: column;
            gap: 10px;
            align-items: stretch !important;
        }
        .d-flex.justify-content-between h4 {
            font-size: 1.05rem;
        }
        .d-flex.justify-content-between .btn {
            width: 100%;
            min-height: 44px;
        }

        .alert.small {
            font-size: 0.85rem;
            padding: 10px 12px;
        }

        /* جدول → کارت */
        table.mobile-as-cards thead { display: none; }
        table.mobile-as-cards,
        table.mobile-as-cards tbody,
        table.mobile-as-cards tr,
        table.mobile-as-cards td {
            display: block;
            width: 100%;
        }
        table.mobile-as-cards tr {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 8px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        table.mobile-as-cards td {
            border: none !important;
            padding: 6px 8px !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        table.mobile-as-cards td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            font-size: 0.78rem;
            flex-shrink: 0;
        }
        table.mobile-as-cards td:last-child {
            border-top: 1px dashed #dee2e6 !important;
            padding-top: 10px !important;
            margin-top: 4px;
            justify-content: flex-start;
            flex-wrap: wrap;
            gap: 6px;
        }
        table.mobile-as-cards td:last-child::before { display: none; }

        table.mobile-as-cards td code {
            font-size: 0.82rem;
            word-break: break-all;
        }
        table.mobile-as-cards td .btn {
            font-size: 0.82rem;
            padding: 6px 10px;
            min-height: 38px;
        }
        table.mobile-as-cards td form {
            display: block;
            width: 100%;
            margin-top: 6px;
        }
        table.mobile-as-cards td form .btn {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">برگه اطلاعات ورود</h4>
        <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">بازگشت</a>
    </div>

    <div class="alert alert-info small">
        <strong>راهنما:</strong>
        روی دکمه «📋 کپی برای شاد» هر ردیف بزنید، متن آماده می‌شود، در شاد برای دانش‌آموز پیام خصوصی بفرستید.
        <br>
        رمز اولیه تا وقتی دانش‌آموز خودش رمز را تغییر دهد، اینجا نمایش داده می‌شود.
    </div>

    <table class="table table-bordered align-middle bg-white mobile-as-cards">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>ایمیل (نام کاربری)</th>
                <th>رمز اولیه</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                @php
                    $service = new \App\Services\StudentCredentialService();
                    $message = $service->buildShadMessage(
                        $student->name,
                        $student->email,
                        $student->initial_password ?? '(قبلاً تغییر داده)',
                        url('/login')
                    );
                @endphp
                <tr>
                    <td data-label="#">{{ $student->id }}</td>
                    <td data-label="نام"><strong>{{ $student->name }}</strong></td>
                    <td data-label="ایمیل" dir="ltr"><code>{{ $student->email }}</code></td>
                    <td data-label="رمز اولیه">
                        @if($student->initial_password)
                            <code class="text-success">{{ $student->initial_password }}</code>
                        @else
                            <span class="text-muted small">— (تغییر داده)</span>
                        @endif
                    </td>
                    <td data-label="وضعیت">
                        @if($student->must_change_password)
                            <span class="badge bg-warning text-dark">در انتظار تغییر رمز</span>
                        @else
                            <span class="badge bg-success">فعال</span>
                        @endif
                    </td>
                    <td data-label="عملیات">
                        @if($student->initial_password)
                            <button type="button" class="btn btn-sm btn-primary copy-shad-btn" data-message="{{ e($message) }}">📋 کپی برای شاد</button>
                        @endif
                        <form action="{{ route('admin.students.reset-password', $student) }}" method="POST" class="d-inline" onsubmit="return confirm('رمز جدید تولید شود؟ رمز فعلی باطل می‌شود.')">
                            @csrf
                            <button class="btn btn-sm btn-warning">🔄 بازنشانی رمز</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">هیچ دانش‌آموزی وجود ندارد.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
        <div id="copyToast" class="toast align-items-center text-bg-success border-0">
            <div class="d-flex">
                <div class="toast-body">متن در کلیپ‌بورد کپی شد ✅</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        document.querySelectorAll('.copy-shad-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const text = this.dataset.message;
                navigator.clipboard.writeText(text).then(function () {
                    new bootstrap.Toast(document.getElementById('copyToast')).show();
                }).catch(function () {
                    alert('کپی ناموفق بود.');
                });
            });
        });
    </script>
@endsection
