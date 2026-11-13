@extends('layouts.panel')

@section('title', $exercise->title)

@section('head')
<style>
    .exercise-description {
        font-size: 1.05rem;
        line-height: 1.95;
        text-align: right;
        direction: rtl;
        white-space: pre-wrap;
        padding: 12px 4px;
    }
    .exercise-title-main {
        font-size: 1.4rem;
        font-weight: 600;
        margin-bottom: 0;
    }
    .code-editor-wrapper {
        display: flex;
        flex-direction: row;
        border: 1px solid #ced4da;
        border-radius: 6px;
        overflow: hidden;
        font-family: 'Fira Code', 'Consolas', 'Monaco', monospace;
        font-size: 0.92rem;
        background: #1e1e1e;
        color: #d4d4d4;
        max-height: 400px;
        direction: ltr;
        font-variant-ligatures: none;
    }
    .line-numbers-col {
        background: #252526;
        color: #858585;
        padding: 10px 8px;
        text-align: right;
        user-select: none;
        min-width: 48px;
        white-space: pre;
        line-height: 1.6;
        overflow: hidden;
        border-right: 1px solid #3c3c3c;
        font-family: 'Fira Code', 'Consolas', 'Monaco', monospace;
        font-size: 0.88rem;
    }
    .code-textarea {
        flex-grow: 1;
        background: #1e1e1e;
        color: #d4d4d4;
        border: none;
        padding: 10px;
        outline: none;
        resize: none;
        font-family: 'Fira Code', 'Consolas', 'Monaco', monospace;
        font-size: 0.92rem;
        line-height: 1.6;
        direction: ltr;
        text-align: left;
        white-space: pre;
        overflow: auto;
        font-variant-ligatures: none;
    }
    .editor-toolbar {
        background: #2d2d2d;
        color: #9cdcfe;
        padding: 6px 12px;
        font-size: 0.78rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* ============ موبایل ============ */
    @media (max-width: 991.98px) {
        .exercise-title-main {
            font-size: 1.15rem !important;
            line-height: 1.5;
        }
        .exercise-description {
            font-size: 1rem;
            line-height: 1.85;
            padding: 8px 0;
        }

        /* کارت اطلاعات تمرین */
        .card .card-body {
            padding: 12px 10px !important;
        }
        .card-header {
            padding: 10px 12px;
            font-size: 0.92rem;
        }

        /* کد ارسال‌شده */
        .code-block-wrapper pre[class*="language-"] {
            font-size: 0.78rem;
            max-height: 300px;
            -webkit-overflow-scrolling: touch;
        }

        /* ادیتور کد: مخفی کردن شماره خطوط، textarea تمام عرض */
        .line-numbers-col { display: none !important; }
        .code-editor-wrapper {
            max-height: none !important;
        }
        .code-textarea {
            min-height: 200px;
            font-size: 0.9rem !important;
            line-height: 1.5;
        }
        .editor-toolbar {
            font-size: 0.75rem;
        }
        .editor-toolbar .d-flex {
            padding: 6px 10px !important;
        }

        /* فیلدها */
        .form-control,
        .form-select {
            font-size: 1rem;
            min-height: 44px;
        }
        textarea.form-control {
            min-height: auto;
        }

        /* دکمه ارسال */
        .btn-lg {
            width: 100%;
            min-height: 52px;
            font-size: 1.05rem;
        }

        /* کارت تصحیح شده */
        .card.border-success .row.g-3 > [class*="col-"] {
            width: 100%;
            margin-bottom: 8px;
        }
        .card.border-success .row.g-3 > [class*="col-"]:last-child {
            margin-bottom: 0;
        }
        .card.border-success .border.rounded.text-center h2 {
            font-size: 2rem;
        }

        /* فاصله */
        .mb-3 { margin-bottom: 12px !important; }
    }
</style>
@endsection

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="exercise-title-main">{{ $exercise->title }}</h3>
        <a href="{{ route('panel.exercises.index') }}" class="btn btn-secondary">بازگشت</a>
    </div>

    @php
        $status = $exercise->statusLabel();
        $canSubmit = $exercise->isAcceptingSubmissions();
    @endphp

    {{-- اطلاعات تمرین --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="small text-muted mb-2">
                <span class="badge bg-info">{{ $exercise->classSubject?->subject?->name }}</span>
                <span class="badge bg-{{ $status['color'] }}">{{ $status['label'] }}</span>
                @if($exercise->requires_code)
                    <span class="badge bg-primary">💻 کدنویسی</span>
                @endif
            </div>

            @if($exercise->deadline_at)
                <div class="mb-2">
                    ⏰ مهلت: <strong>{{ jdate($exercise->deadline_at, 'EEEE d MMMM yyyy HH:mm') }}</strong>
                </div>
            @endif

            <div class="mb-2">
                🎯 حداکثر نمره: <strong>{{ $exercise->max_score }}</strong>
            </div>

            <hr>

            <h6 class="mb-2">متن تمرین:</h6>
            <div class="exercise-description">{{ $exercise->description }}</div>

            @if($exercise->attachment_path)
                <hr>
                <a href="{{ URL::temporarySignedRoute('files.download', now()->addMinutes(60), ['path' => $exercise->attachment_path]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    📎 دانلود فایل ضمیمه
                </a>
            @endif
        </div>
    </div>

    {{-- اگر تصحیح شده --}}
    @if($submission && $submission->isGraded())
        <div class="card border-success mb-3">
            <div class="card-header bg-success text-white">
                <strong>✅ پاسخ شما تصحیح شده است</strong>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded text-center p-3">
                            <div class="small text-muted">نمره شما</div>
                            <h2 class="mb-0 text-success">{{ $submission->score }}</h2>
                            <small class="text-muted">از {{ $exercise->max_score }}</small>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="small text-muted">تاریخ تصحیح:</div>
                        <div>{{ jdate($submission->graded_at, 'EEEE d MMMM yyyy HH:mm') }}</div>
                        @if($submission->is_late)
                            <span class="badge bg-warning text-dark mt-2">ارسال با تأخیر</span>
                        @endif
                    </div>
                </div>

                @if($submission->feedback)
                    <div class="alert alert-info mb-0">
                        <strong>بازخورد معلم:</strong>
                        <div class="mt-2" style="white-space: pre-wrap;">{{ $submission->feedback }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- نمایش پاسخ ثبت‌شده --}}
        <div class="card mb-3">
            <div class="card-header"><strong>پاسخ ثبت‌شده شما</strong></div>
            <div class="card-body">
                @if($submission->content)
                    <h6>متن پاسخ:</h6>
                    <div class="border rounded p-3 bg-light mb-3" style="white-space: pre-wrap; text-align: right;">{{ $submission->content }}</div>
                @endif
                @if($submission->code_content)
                    <h6>کد ارسال‌شده:</h6>
                    <x-code-block :code="$submission->code_content" label="پاسخ کدنویسی" />
                @endif
                @if($submission->file_path)
                    <div class="mt-2">
                        <a href="{{ URL::temporarySignedRoute('files.download', now()->addMinutes(60), ['path' => $submission->file_path]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            📎 فایل ارسال‌شده
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- فرم پاسخ --}}
    @if($canSubmit && (!$submission || !$submission->isGraded()))
        <div class="card mb-3">
            <div class="card-header">
                <strong>{{ $submission ? 'ویرایش پاسخ' : 'ارسال پاسخ' }}</strong>
                @if($submission)
                    <small class="text-muted">— آخرین ارسال: {{ jdate($submission->submitted_at, 'yyyy/MM/dd HH:mm') }}</small>
                @endif
            </div>
            <div class="card-body">

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                @endif

                <form action="{{ route('panel.exercises.submit', $exercise) }}" method="POST" enctype="multipart/form-data" id="exerciseForm">
                    @csrf

                    {{-- متن پاسخ (همیشه نمایش) --}}
                    <div class="mb-3">
                        <label class="form-label">
                            {{ $exercise->requires_code ? 'توضیح (اختیاری)' : 'متن پاسخ' }}
                        </label>
                        <textarea name="content" class="form-control" rows="{{ $exercise->requires_code ? 3 : 8 }}"
                                  placeholder="{{ $exercise->requires_code ? 'اگر توضیحی دارید اینجا بنویسید...' : 'پاسخ خود را اینجا بنویسید...' }}">{{ old('content', $submission?->content) }}</textarea>
                    </div>

                    {{-- فیلد کد (اگر تمرین کدنویسی است) --}}
                    @if($exercise->requires_code)
                        <div class="mb-3">
                            <label class="form-label">کد شما <span class="text-danger">*</span></label>
                            <div class="code-editor-wrapper" dir="ltr">
                                <div class="editor-toolbar" style="display: block; padding: 0; width: 100%;">
                                    <div class="d-flex justify-content-between align-items-center px-2 py-1" style="background:#2d2d2d;">
                                        <span>📝 code.php</span>
                                        <span id="lineCount">{{ count(explode("\n", old('code_content', $submission?->code_content ?? '') ?: ' ')) }} خط</span>
                                    </div>
                                </div>
                            </div>
                            <div class="code-editor-wrapper" dir="ltr" style="border-top: none; border-radius: 0 0 6px 6px;">
                                <div class="line-numbers-col" id="lineNumbers"></div>
                                <textarea name="code_content" id="codeInput" class="code-textarea" rows="12"
                                          spellcheck="false"
                                          autocorrect="off"
                                          autocomplete="off"
                                          autocapitalize="off"
                                          placeholder="// کد خود را اینجا بنویسید...">{{ old('code_content', $submission?->code_content) }}</textarea>
                            </div>
                            <small class="text-muted">با Tab می‌توانید تودرتو کنید.</small>
                        </div>
                    @endif

                    {{-- فیلد فایل (اگر مجاز است) --}}
                    @if($exercise->allow_file_upload)
                        <div class="mb-3">
                            <label class="form-label">فایل پاسخ (اختیاری — مثلاً .zip پروژه)</label>
                            <input type="file" name="file" class="form-control">
                            <small class="text-muted">
                                حداکثر ۱۰ مگابایت.
                                @if($submission?->file_path)
                                    <br>فایل فعلی:
                                    <a href="{{ URL::temporarySignedRoute('files.download', now()->addMinutes(60), ['path' => $submission->file_path]) }}" target="_blank">دانلود</a>
                                    (با آپلود فایل جدید جایگزین می‌شود)
                                @endif
                            </small>
                        </div>
                    @endif

                    <button class="btn btn-success btn-lg" onclick="return confirm('پاسخ خود را ارسال می‌کنید؟ تا قبل از تصحیح معلم می‌توانید تغییر دهید.')">
                        {{ $submission ? 'به‌روزرسانی پاسخ' : 'ارسال پاسخ' }}
                    </button>
                </form>
            </div>
        </div>
    @elseif($submission && !$submission->isGraded())
        <div class="alert alert-info">
            پاسخ شما ثبت شده و در انتظار تصحیح معلم است. می‌توانید تا قبل از تصحیح، پاسخ خود را تغییر دهید.
        </div>
    @elseif(!$canSubmit)
        <div class="alert alert-warning">
            این تمرین در حال حاضر پاسخ نمی‌پذیرد.
        </div>
    @endif

@endsection

@push('scripts')
@if($exercise->requires_code)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const codeInput = document.getElementById('codeInput');
    const lineNumbers = document.getElementById('lineNumbers');
    const lineCount = document.getElementById('lineCount');

    function updateLineNumbers() {
        const lines = codeInput.value.split('\n').length;
        let html = '';
        for (let i = 1; i <= lines; i++) html += i + '\n';
        lineNumbers.textContent = html;
        lineNumbers.scrollTop = codeInput.scrollTop;
        lineCount.textContent = lines + ' خط';
    }

    // همگام‌سازی اسکرول شماره‌ها با textarea
    codeInput.addEventListener('scroll', () => {
        lineNumbers.scrollTop = codeInput.scrollTop;
    });

    codeInput.addEventListener('input', updateLineNumbers);
    updateLineNumbers();

    // Tab برای تودرتو کردن
    codeInput.addEventListener('keydown', function (e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
            this.selectionStart = this.selectionEnd = start + 4;
            updateLineNumbers();
        }
    });
});
</script>
@endif
@endpush
