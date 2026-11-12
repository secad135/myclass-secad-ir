@extends('layouts.admin')

@section('title', 'برگه اطلاعات ورود')

@section('head')
<style>
    /* textarea مخفی اما قابل دسترسی برای کپی */
    .copy-source {
        position: fixed !important;
        top: 0 !important;
        left: -9999px !important;
        opacity: 0;
        pointer-events: none;
        width: 1px;
        height: 1px;
        border: 0;
        padding: 0;
        margin: 0;
        resize: none;
    }

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
        table.mobile-as-cards thead {
            display: none;
        }

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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
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

        table.mobile-as-cards td:last-child::before {
            display: none;
        }

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

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

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
                    $student->initial_password ?? '(قبلاً تغییر داده)'
                );
            @endphp
            <tr>
                <td data-label="#">{{ $student->id }}</td>
                <td data-label="نام">{{ $student->name }}</td>
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
                        {{-- textarea مخفی برای نگهداری متن (نه با d-none، بلکه خارج از کادر دید) --}}
                        <textarea class="copy-source" id="msg-{{ $student->id }}">{{ $message }}</textarea>

                        <button type="button" class="btn btn-sm btn-primary copy-btn" data-target="msg-{{ $student->id }}">
                            📋 کپی برای شاد
                        </button>
                    @endif
                    <form action="{{ route('admin.students.reset-password', $student) }}" method="POST" class="d-inline" onsubmit="return confirm('رمز جدید تولید شود؟ رمز فعلی باطل می‌شود.')">
                        @csrf
                        <button class="btn btn-sm btn-warning">🔄 بازنشانی رمز</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted">هیچ دانش‌آموزی وجود ندارد.</td>
            </tr>
        @endforelse
    </tbody>
</table>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // نمایش بازخورد بصری روی دکمه
    function showFeedback(btn, success) {
        const originalHTML = btn.innerHTML;
        const originalClass = btn.className;

        if (success) {
            btn.innerHTML = '✅ کپی شد!';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
        } else {
            btn.innerHTML = '❌ کپی نشد';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-danger');
        }

        setTimeout(function () {
            btn.innerHTML = originalHTML;
            btn.className = originalClass;
        }, 2000);
    }

    // تابع کپی با دو روش (اول مدرن، بعد قدیمی)
    async function copyText(text) {
        // روش مدرن Clipboard API (فقط HTTPS یا localhost)
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (err) {
                console.warn('navigator.clipboard failed, falling back:', err);
            }
        }

        // روش قدیمی به عنوان پشتیبان
        try {
            const temp = document.createElement('textarea');
            temp.value = text;
            temp.setAttribute('readonly', '');

            // باید داخل کادر دید باشد؛ متن روی صفحه دیده نمی‌شود ولی انتخاب/کپی موبایل کار می‌کند
            temp.style.cssText = [
                'position:fixed',
                'top:0',
                'left:0',
                'width:2em',
                'height:2em',
                'padding:0',
                'border:0',
                'outline:none',
                'box-shadow:none',
                'background:transparent',
                'opacity:0.01',
                'font-size:16px',
                'z-index:999999'
            ].join(';');

            document.body.appendChild(temp);
            temp.focus({ preventScroll: true });
            temp.select();
            temp.setSelectionRange(0, text.length);

            const ok = document.execCommand('copy');
            document.body.removeChild(temp);
            return ok;
        } catch (err) {
            console.error('execCommand failed:', err);
            return false;
        }
    }

    // اتصال به همه دکمه‌ها
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.copy-btn').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const targetId = this.getAttribute('data-target');
                const textarea = document.getElementById(targetId);

                if (!textarea) {
                    alert('متن قابل کپی یافت نشد.');
                    return;
                }

                const ok = await copyText(textarea.value);
                showFeedback(this, ok);

                if (!ok) {
                    alert('کپی ناموفق بود. لطفاً متن را دستی انتخاب و کپی کنید.');
                }
            });
        });
    });
})();
</script>
@endpush