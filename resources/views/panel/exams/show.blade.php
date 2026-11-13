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
    .exam-watermark {
        position: fixed;
        inset: 0;
        z-index: 1000;
        pointer-events: none;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        line-height: 1.8;
        font-size: clamp(1.4rem, 6vw, 4rem);
        font-weight: 700;
        color: #000;
        opacity: 0.055;
        transform: rotate(-25deg);
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
    .question-text {
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .option-text {
        flex: 1 1 auto;
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
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
        body { padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px)); }

        .container-fluid {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        .timer-fixed {
            position: sticky;
            top: 0;
            bottom: auto;
            left: auto;
            transform: none;
            margin: 0 auto 12px;
            width: max-content;
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
        body { display: none !important; }
    }
</style>
@endsection

@section('content')

    <div id="timer" class="timer-fixed">{{ gmdate('H:i:s', $remaining) }}</div>
    <div class="exam-watermark" aria-hidden="true">
        {{ auth()->user()->name }}<br>
        تلاش #{{ $attempt->attempt_number }} — {{ jdate(now(), 'yyyy/MM/dd HH:mm') }}
    </div>

    <div class="container-fluid pt-3" style="max-width: 950px;">
        <h3 class="mb-1">{{ $attempt->exam->title }}</h3>
        <p class="text-muted small">
            {{ $attempt->exam->classSubject?->subject?->name }} —
            تلاش #{{ $attempt->attempt_number }}
            | {{ $attempt->total_questions }} سوال | نمره از ۲۰
        </p>

        <div class="alert alert-warning small">
            ⚠️ <strong>توجه:</strong> پس از شروع، نمی‌توانید آزمون را متوقف کنید. با پایان زمان، آزمون به‌طور خودکار ثبت می‌شود.
            اگر صفحه را ببندید، پاسخ‌های انتخاب‌شده ذخیره می‌شوند و می‌توانید ادامه دهید.
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

                <div class="question-card card {{ $answer->selected_option_id ? 'answered' : '' }}" id="card_{{ $question->id }}">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <span class="question-number">{{ $idx + 1 }}</span>
                            <div class="flex-grow-1">
                                <div class="mb-2 question-text" style="font-size: 1.02rem;">
                                    {!! nl2br(e($question->text)) !!}
                                </div>

                                @if($question->code_snippet)
                                    <x-code-block :code="$question->code_snippet" label="کد سوال" />
                                @endif
                            </div>
                        </div>

                        <div class="mt-3">
                            @foreach($options as $optIdx => $option)
                                <label class="option-label {{ $answer->selected_option_id == $option->id ? 'selected' : '' }}" data-qid="{{ $question->id }}">
                                    <input type="radio"
                                           name="answers[{{ $question->id }}]"
                                           value="{{ $option->id }}"
                                           data-qid="{{ $question->id }}"
                                           class="form-check-input me-2"
                                           @checked($answer->selected_option_id == $option->id)>
                                    <span class="fw-bold me-1">{{ $optIdx + 1 }})</span>
                                    <span class="option-text">{{ $option->text }}</span>
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
    var SAVE_URL    = "{{ route('panel.exams.save', $attempt) }}";
    var CSRF_TOKEN  = "{{ csrf_token() }}";
    var submitted   = false;
    var timerInterval = null;
    var saveTimer = null;
    var saving = false;
    
    // ⚡️ زمان باقی‌مانده از سرور گرفته می‌شود و با ساعت یکنواخت مرورگر
    // (performance.now) کاهش می‌یابد؛ بنابراین ساعت اشتباه گوشی تاثیری ندارد.
    var SERVER_REMAINING = {{ (int) $remaining }};
    var remaining = SERVER_REMAINING;
    var remainingBaseline = (window.performance && performance.now) ? performance.now() : Date.now();

    function elapsedSeconds() {
        var now = (window.performance && performance.now) ? performance.now() : Date.now();
        return Math.floor((now - remainingBaseline) / 1000);
    }

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
            window.location.reload();
        }
    });

    function logCheat(reason) {
        fetch("{{ route('panel.exams.log-cheat', $attempt) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ reason: reason })
        }).then(function (r) {
            if (!r.ok) throw new Error('log failed');
            console.warn('تقلب ثبت شد: ' + reason);
        }).catch(function () {});

        // نمایش یک هشدار قرمز به دانش‌آموز
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-2 shadow';
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = '⚠️ <strong>هشدار تقلب:</strong> خروج از صفحه آزمون ثبت شد!';
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 3000);
    }

    // ۲. جلوگیری از راست کلیک و کپی
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

            // ذخیره خودکار پاسخ‌ها روی سرور هر ۱۵ ثانیه
            setInterval(function () {
                if (!submitted) saveAnswersToServer();
            }, 15000);

            // آخرین ذخیره هنگام بستن یا ترک صفحه
            window.addEventListener('pagehide', function () {
                if (!submitted) beaconSave();
            });
            
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
            if (document.querySelector('input[name="answers[' + qid + ']"]:checked')) {
                return;
            }

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

    function collectAnswers() {
        var data = {};
        document.querySelectorAll('input[type=radio][name^="answers"]:checked').forEach(function (r) {
            var match = r.name.match(/answers\[(\d+)\]/);
            if (match) data[match[1]] = r.value;
        });
        return data;
    }

    function saveAnswers() {
        var data = collectAnswers();
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)); } catch (e) {}
    }

    function saveAnswersToServer() {
        if (submitted || saving) return;
        saving = true;

        fetch(SAVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({ answers: collectAnswers() })
        })
        .then(function (r) {
            return r.json().catch(function () { return {}; });
        })
        .then(function (data) {
            if (data.status && data.status !== 'in_progress') {
                submitted = true;
                window.location.href = "{{ route('panel.exams.result', $attempt) }}";
            }
        })
        .catch(function () {})
        .finally(function () { saving = false; });
    }

    function scheduleServerSave() {
        if (submitted) return;
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveAnswersToServer, 800);
    }

    function beaconSave() {
        if (!navigator.sendBeacon) return;

        var data = collectAnswers();
        var formData = new FormData();
        formData.append('_token', CSRF_TOKEN);

        Object.keys(data).forEach(function (qid) {
            formData.append('answers[' + qid + ']', data[qid]);
        });

        navigator.sendBeacon(SAVE_URL, formData);
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
        
        // کاهش زمان از روی ساعت یکنواخت مرورگر (مستقل از ساعت گوشی)
        remaining = Math.max(0, SERVER_REMAINING - elapsedSeconds());
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
                scheduleServerSave();
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

})();
</script>
@endpush
