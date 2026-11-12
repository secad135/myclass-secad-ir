@php
$isTeacher = auth()->user()->is_teacher;
$panelPrefix = $isTeacher ? 'teacher' : 'admin';
$layout = $isTeacher ? 'layouts.teacher' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', 'ورود گروهی سوالات')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">ورود گروهی سوالات (JSON)</h4>
        <div class="d-flex gap-2">
            <a href="{{ route($panelPrefix . '.questions.template') }}" class="btn btn-outline-success">
                📥 دانلود فایل نمونه
            </a>
            <a href="{{ route($panelPrefix . '.questions.index') }}" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(session('import_errors'))
        <div class="alert alert-danger">
            <strong>خطا در اعتبارسنجی — هیچ سوالی وارد نشد:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-info small">
        <strong>راهنما:</strong>
        ابتدا مبحث را انتخاب کنید، سپس فایل JSON آپلود کنید یا متن آن را جای‌گذاری کنید.
        همه سوالات به همان مبحث اضافه می‌شوند.
        <br>
        اگر خطایی رخ دهد، هیچ سوالی ذخیره نمی‌شود (همه یا هیچ).
    </div>

    <form action="{{ route($panelPrefix . '.questions.import.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded">
        @csrf

        <div class="mb-3">
            <label class="form-label">مبحث</label>
            <select name="topic_id" class="form-select" required>
                <option value="">— انتخاب مبحث —</option>
                @foreach($topics as $t)
                    <option value="{{ $t->id }}" @selected(old('topic_id') == $t->id)>
                        {{ $t->subject?->name }} — {{ $t->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">روش ورود</label>
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-file" type="button">
                        📁 آپلود فایل
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-text" type="button">
                        ✏️ جای‌گذاری متن
                    </button>
                </li>
            </ul>

            <div class="tab-content border border-top-0 p-3">
                <div class="tab-pane fade show active" id="tab-file">
                    <input type="file" name="json_file" accept=".json,.txt" class="form-control">
                    <small class="text-muted">حداکثر ۵ مگابایت — فرمت: .json یا .txt</small>
                </div>
                <div class="tab-pane fade" id="tab-text">
                    <textarea name="json_text" class="form-control" rows="12" dir="ltr"
                              style="font-family: monospace; font-size: 0.85rem;"
                              placeholder='{{ "{\n  \"questions\": [ ... ]\n}" }}'>{{ old('json_text') }}</textarea>
                </div>
            </div>
        </div>

        <button class="btn btn-success btn-lg">📥 شروع ورود سوالات</button>
    </form>

    <div class="card mt-4">
        <div class="card-header"><strong>📋 فرمت مورد نیاز فایل JSON</strong></div>
        <div class="card-body">
            <p class="small text-muted mb-2">
                فایل باید یک آبجکت با کلید <code>questions</code> باشد که آرایه‌ای از سوالات دارد.
                هر سوال باید <strong>دقیقاً ۴ گزینه</strong> و <strong>دقیقاً یک پاسخ صحیح</strong> داشته باشد.
            </p>

            <pre class="bg-dark text-light p-3 rounded" dir="ltr" style="font-size: 0.8rem; max-height: 400px; overflow-y: auto;"><code>{
  "questions": [
    {
      "text": "کدام حلقه برای تکرار تعداد مشخصی مناسب است؟",
      "code_snippet": null,
      "difficulty": "easy",
      "options": [
        { "text": "for",    "is_correct": true  },
        { "text": "if",     "is_correct": false },
        { "text": "while",  "is_correct": false },
        { "text": "switch", "is_correct": false }
      ]
    },
    {
      "text": "خروجی این کد چیست؟",
      "code_snippet": "for ($i = 0; $i < 5; $i++) {\n    echo $i;\n}",
      "difficulty": "medium",
      "options": [
        { "text": "012345", "is_correct": false },
        { "text": "01234",  "is_correct": true  },
        { "text": "12345",  "is_correct": false },
        { "text": "1234",   "is_correct": false }
      ]
    }
  ]
}</code></pre>

            <details class="mt-3">
                <summary class="small text-primary" style="cursor: pointer;">فرمت جایگزین (کوتاه‌تر)</summary>
                <pre class="bg-dark text-light p-3 rounded mt-2" dir="ltr" style="font-size: 0.8rem;"><code>{
  "questions": [
    {
      "text": "کدام حلقه برای تکرار تعداد مشخصی مناسب است؟",
      "difficulty": "easy",
      "options": ["for", "if", "while", "switch"],
      "correct": 0
    }
  ]
}</code></pre>
                <p class="small text-muted mb-0">
                    در این فرمت، <code>correct</code> شماره گزینه صحیح است (از ۰ شروع می‌شود).
                </p>
            </details>

            <div class="mt-3">
                <strong class="small">سطح سختی:</strong>
                <span class="badge bg-success">easy</span>
                <span class="badge bg-warning text-dark">medium</span>
                <span class="badge bg-danger">hard</span>
                <small class="text-muted d-block mt-1">اگر خالی بگذارید، «medium» در نظر گرفته می‌شود.</small>
            </div>
        </div>
    </div>

@endsection
