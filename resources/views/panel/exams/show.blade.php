@extends('layouts.panel')

@section('title', $attempt->exam->title)

@section('head')
<style>
        /* غیرفعال کردن انتخاب متن در کل صفحه آزمون */
    body {
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }
    /* استثنا: فیلدهای ورودی باید کار کنند */
    input, textarea, select {
        user-select: text !important;
        -webkit-user-select: text !important;
    }
    .timer-fixed {
        position: fixed;
        top: 60px;
        left: 20px;
        z-index: 1000;
        background: #212529;
        color: white;
        padding: 12px 18px;
        border-radius: 8px;
        font-size: 1.4rem;
        font-weight: bold;
        font-family: monospace;
        min-width: 130px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    .timer-fixed.warning { background: #d97706; }
    .timer-fixed.danger {
        background: #dc3545;
        animation: pulse 1s infinite;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    .question-card {
        border-right: 4px solid #0d6efd;
        margin-bottom: 16px;
        background: white;
    }
    .question-card.answered { border-right-color: #198754; }
    .question-number {
        background: #0d6efd;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    .option-label {
        display: block;
        padding: 10px 14px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 6px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .option-label:hover { background: #f8f9fa; border-color: #adb5bd; }
    .option-label.selected {
        background: #cfe2ff;
        border-color: #0d6efd;
    }
    .question-card .flex-grow-1 {
        min-width: 0;
    }
    .code-snippet {
        background: #212529;
        color: #e9ecef;
        padding: 12px;
        border-radius: 6px;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        overflow-x: auto;
        direction: ltr;
        text-align: left;
        margin: 8px 0;
        white-space: pre;
        max-width: 100%;
        box-sizing: border-box;
    }

    /* ============ موبایل ============ */
    @media (max-width: 991.98px) {
        body { padding-bottom: 90px; }

        .container-fluid {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        .timer-fixed {
            top: auto;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 22px;
            font-size: 1.25rem;
            min-width: 120px;
            border-radius: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.45);
            z-index: 1100;
        }
        .timer-fixed.danger { animation: pulse 0.8s infinite; }

        .container-fluid > h3 {
            font-size: 1.1rem !important;
            line-height: 1.5;
        }
        .container-fluid > p.text-muted {
            font-size: 0.82rem;
        }
        .alert.small {
            font-size: 0.85rem;
            padding: 10px 12px;
        }

        .question-card {
            border-right-width: 5px;
            margin-bottom: 12px;
        }
        .question-card .card-body {
            padding: 12px 10px !important;
        }
        .question-card .mb-2[style*="1.02rem"] {
            font-size: 1.02rem !important;
            line-height: 1.75;
        }

        .question-number {
            width: 28px;
            height: 28px;
            font-size: 0.85rem;
        }

        .option-label {
            padding: 13px 12px;
            font-size: 1rem;
            min-height: 52px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        .option-label .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0;
            flex-shrink: 0;
        }
        .option-label .fw-bold {
            min-width: 22px;
        }

        .code-snippet {
            font-size: 0.78rem;
            padding: 10px;
            margin: 8px 0;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
            white-space: pre-wrap;
            word-break: break-word;
            overflow-wrap: anywhere;
            overflow-x: hidden;
        }

        .text-center.my-4 .btn-lg {
            width: 100%;
            min-height: 52px;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
    }

    @media print {
        .timer-fixed { display: none !important; }
    }
</style>
@endsection

@section('content')

    <div id="timer" class="timer-fixed">{{ gmdate('H:i:s', $remaining) }}</div>

    <div class="container-fluid pt-3" style="max-width: 950px;">
        <h3 class="mb-1">{{ $attempt->exam->title }}</h3>
        <p class="text-muted small">
            {{ $attempt->exam->classSubject?->subject?->name }} —
            تلاش #{{ $attempt->attempt_number }}
            | {{ $attempt->total_questions }} سوال | نمره از ۲۰
        </p>

        <div class="alert alert-warning small">
            ⚠️ <strong>توجه:</strong> پس از شروع، نمی‌توانید آزمون را متوقف کنید. با پایان زمان، آزمون به‌طور خودکار ثبت می‌شود.
            اگر صفحه را ببندید، پاسخ‌های انتخاب‌شده در مرورگر شما ذخیره می‌شوند و می‌توانید ادامه دهید.
        </div>

        <form id="examForm" action="{{ route('panel.exams.submit', $attempt) }}" method="POST">
            @csrf

            @foreach($attempt->answers as $idx => $answer)
                @php
                    $question = $answer->question;
                    $options = collect($answer->options_order)
                        ->map(fn($id) => $question->options->firstWhere('id', $id))
                        ->filter();
                @endphp

                <div class="question-card card" id="card_{{ $question->id }}">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <span class="question-number">{{ $idx + 1 }}</span>
                            <div class="flex-grow-1">
                                <div class="mb-2" style="font-size: 1.02rem;">
                                    {!! nl2br(e($question->text)) !!}
                                </div>

                                @if($question->code_snippet)
                                    <x-code-block :code="$question->code_snippet" label="کد سوال" />
                                @endif
                            </div>
                        </div>

                        <div class="mt-3">
                            @foreach($options as $optIdx => $option)
                                <label class="option-label" data-qid="{{ $question->id }}">
                                    <input type="radio"
                                           name="answers[{{ $question->id }}]"
                                           value="{{ $option->id }}"
                                           data-qid="{{ $question->id }}"
                                           class="form-check-input me-2">
                                    <span class="fw-bold me-1">{{ $optIdx + 1 }})</span>
                                    <span style="font-family: 'Courier New', monospace;">{{ $option->text }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="text-center my-4">
                <div class="small text-muted mb-2" id="answeredCounter">
                    پاسخ داده‌شده: <strong id="answeredCount">0</strong> از {{ $attempt->total_questions }}
                </div>
                <button type="button" class="btn btn-success btn-lg px-5" onclick="confirmSubmit()">
                    ✅ ثبت نهایی آزمون
                </button>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var ATTEMPT_ID  = {{ $attempt->id }};
    var STORAGE_KEY = 'exam_attempt_' + ATTEMPT_ID;
    var TOTAL_Q     = {{ $attempt->total_questions }};
    var submitted   = false;
    var timerInterval = null;
    
    // ⚡️ محاسبه دقیق زمان از روی ساعت سیستم (نه از مقدار سرور که در کش می‌ماند)
    var ATTEMPT_STARTED_AT = {{ $attempt->started_at->timestamp }};
    var ATTEMPT_DURATION   = {{ $attempt->exam->duration_minutes * 60 }};
    var SERVER_REMAINING   = {{ (int) $remaining }};
    
    var wallClockRemaining = Math.max(0, (ATTEMPT_STARTED_AT + ATTEMPT_DURATION) - Math.floor(Date.now() / 1000));
    
    // اگر اختلاف زمان محاسبه‌شده با زمان سرور بیش از ۱۰ ثانیه بود، صفحه از کش آمده → رفرش کن
    if (Math.abs(wallClockRemaining - SERVER_REMAINING) > 10) {
        window.location.reload(true);
    }
    
    var remaining = wallClockRemaining;

    // ۱. قفل کردن دکمه بازگشت (Back) مرورگر
    history.pushState(null, null, location.href);
    window.addEventListener('popstate', function () {
        if (!submitted) {
            history.pushState(null, null, location.href);
            alert('⚠️ در حین آزمون، امکان بازگشت به صفحه قبل وجود ندارد.');
            logCheat('تلاش برای استفاده از دکمه بازگشت مرورگر');
        }
    });

    // ۲. جلوگیری از تعلیق زمان در حافظه کش مرورگر (BFCache)
    window.addEventListener('pageshow', function (event) {
        if (event.persisted || (performance.getEntriesByType && performance.getEntriesByType('navigation')[0]?.type === 'back_forward')) {
            window.location.reload(true);
        }
    });

    // ۳. ثبت خودکار آزمون هنگام بستن مرورگر یا رفتن به آدرس دیگر
    window.addEventListener('beforeunload', function (e) {
        if (!submitted) {
            // ارسال سیگنال به سرور برای ثبت فوری آزمون
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');

            navigator.sendBeacon("{{ route('panel.exams.force-submit', $attempt) }}", formData);

            // نمایش پیام هشدار مرورگر
            e.preventDefault();
            e.returnValue = 'با خروج از این صفحه، آزمون شما به صورت خودکار ثبت می‌شود و امکان بازگشت وجود ندارد.';
        }
    });

    // ۱. درخواست تمام‌صفحه در شروع آزمون
    document.addEventListener('DOMContentLoaded', function() {
        // اگر در حالت تمام‌صفحه نیستیم، با اولین کلیک کاربر تمام‌صفحه می‌شویم
        if (!document.fullscreenElement) {
            document.body.addEventListener('click', function requestFS() {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log('Fullscreen failed:', err);
                });
                document.body.removeEventListener('click', requestFS);
            }, { once: true });
        }
    });

    // ۲. نظارت بر خروج از تمام‌صفحه
    document.addEventListener('fullscreenchange', function() {
        if (!document.fullscreenElement && !submitted) {
            logCheat('خروج از حالت تمام‌صفحه');
        }
    });

    // ۳. نظارت بر تغییر تب یا مینیمایز شدن
    let localTabSwitches = 0;
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && !submitted) {
            localTabSwitches++;
            logCheat('تغییر تب یا خروج از مرورگر');
        }
    });

    function logCheat(reason) {
        fetch("{{ route('panel.exams.log-cheat', $attempt) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(() => {
            console.warn('تقلب ثبت شد: ' + reason);
        });

        // نمایش یک هشدار قرمز به دانش‌آموز
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-2 shadow';
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = '⚠️ <strong>هشدار تقلب:</strong> خروج از صفحه آزمون ثبت شد!';
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 3000);
    }

    // ۴. جلوگیری از راست کلیک و کپی
    document.addEventListener('contextmenu', event => event.preventDefault());
    document.addEventListener('copy', event => {
        event.preventDefault();
    });

    function safeInit() {
        try {
            restoreAnswers();
            bindRadioEvents();
            updateAnsweredCount();
            updateTimerDisplay();

            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(tickTimer, 1000);
            
            // ارسال ضربان (Heartbeat) به سرور هر ۲۵ ثانیه
            setInterval(function() {
                if (!submitted) {
                    fetch("{{ route('panel.exams.ping', $attempt) }}", {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    }).catch(() => {});
                }
            }, 25000);
            
            // بررسی وضعیت با سرور هنگام بارگذاری (اگر کاربر تب را بسته بوده)
            fetch("{{ route('panel.exams.check', $attempt) }}", {
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'in_progress') {
                    submitted = true;
                    window.location.href = "{{ route('panel.exams.result', $attempt) }}";
                }
            })
            .catch(() => {});
        } catch (e) {
            console.error('Exam init error:', e);
        }
    }

    // ---------- localStorage ----------
    function restoreAnswers() {
        var saved = {};
        try { saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch (e) {}

        Object.keys(saved).forEach(function (qid) {
            var radio = document.querySelector('input[name="answers[' + qid + ']"][value="' + saved[qid] + '"]');
            if (radio) {
                radio.checked = true;
                var label = radio.closest('.option-label');
                if (label) label.classList.add('selected');
                var card = document.getElementById('card_' + qid);
                if (card) card.classList.add('answered');
            }
        });
    }

    function saveAnswers() {
        var data = {};
        document.querySelectorAll('input[type=radio][name^="answers"]:checked').forEach(function (r) {
            var match = r.name.match(/answers\[(\d+)\]/);
            if (match) data[match[1]] = r.value;
        });
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)); } catch (e) {}
    }

    function updateAnsweredCount() {
        var count = document.querySelectorAll('input[type=radio][name^="answers"]:checked').length;
        var el = document.getElementById('answeredCount');
        if (el) el.textContent = count;
    }

    // ---------- تایمر ----------
    function updateTimerDisplay() {
        var timer = document.getElementById('timer');
        if (!timer) return;

        var h = Math.floor(Math.max(0, remaining) / 3600);
        var m = Math.floor((Math.max(0, remaining) % 3600) / 60);
        var s = Math.max(0, remaining) % 60;
        timer.textContent =
            String(h).padStart(2, '0') + ':' +
            String(m).padStart(2, '0') + ':' +
            String(s).padStart(2, '0');

        timer.classList.remove('warning', 'danger');
        if (remaining <= 60) timer.classList.add('danger');
        else if (remaining <= 300) timer.classList.add('warning');
    }

    function tickTimer() {
        if (submitted) return;
        
        // محاسبه مجدد از روی ساعت واقعی (جلوگیری از ریست شدن زمان در کش)
        remaining = Math.max(0, (ATTEMPT_STARTED_AT + ATTEMPT_DURATION) - Math.floor(Date.now() / 1000));
        updateTimerDisplay();

        if (remaining <= 0) {
            autoSubmit();
        }
    }

    function autoSubmit() {
        if (submitted) return;
        submitted = true;
        alert('زمان آزمون به پایان رسید. پاسخ‌های شما به‌طور خودکار ثبت می‌شود.');
        var form = document.getElementById('examForm');
        if (form) form.submit();
    }

    // ---------- رویدادها ----------
    function bindRadioEvents() {
        document.querySelectorAll('input[type=radio][name^="answers"]').forEach(function (r) {
            r.addEventListener('change', function () {
                document.querySelectorAll('input[name="' + this.name + '"]').forEach(function (sib) {
                    var lbl = sib.closest('.option-label');
                    if (lbl) lbl.classList.remove('selected');
                });
                var lbl = this.closest('.option-label');
                if (lbl) lbl.classList.add('selected');

                var card = document.getElementById('card_' + this.dataset.qid);
                if (card) card.classList.add('answered');

                saveAnswers();
                updateAnsweredCount();
            });
        });
    }

    // ---------- ثبت نهایی ----------
    window.confirmSubmit = function () {
        if (submitted) return;

        var count = document.querySelectorAll('input[type=radio][name^="answers"]:checked').length;
        var msg = 'شما به ' + count + ' از ' + TOTAL_Q + ' سوال پاسخ داده‌اید.';
        if (count < TOTAL_Q) {
            msg += '\n\n' + (TOTAL_Q - count) + ' سوال بدون پاسخ مانده و به‌عنوان غلط حساب می‌شوند.\n\n';
        }
        msg += 'آیا از ثبت نهایی آزمون مطمئن هستید؟ (بعد از ثبت، امکان تغییر نیست)';

        if (!confirm(msg)) return;

        submitted = true;
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
        var form = document.getElementById('examForm');
        if (form) form.submit();
    };

    // ---------- اجرا (امن، حتی اگر DOMContentLoaded قبلاً fire شده باشد) ----------
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', safeInit);
    } else {
        safeInit();
    }

    // تشخیص کلید PrintScreen (فقط به عنوان هشدار و ثبت تخلف)
    document.addEventListener('keyup', function(e) {
        if (e.key === 'PrintScreen' && !submitted) {
            // پاک کردن حافظه موقت (کلیپ‌بورد) برای بی‌فایده کردن اسکرین‌شات
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText('').catch(() => {});
            }

            logCheat('اسکرین‌شات گرفتن (PrintScreen)');
            alert('⚠️ هشدار: اسکرین‌شات گرفتن در حین آزمون تخلف است و ثبت شد!');
        }
    });
})();
</script>
@endpush
