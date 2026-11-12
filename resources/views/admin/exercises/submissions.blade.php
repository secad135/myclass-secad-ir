@php
$isTeacher = auth()->user()->is_teacher;
$panelPrefix = $isTeacher ? 'teacher' : 'admin';
$layout = $isTeacher ? 'layouts.teacher' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', 'پاسخ‌های ' . $exercise->title)

@section('head')
<style>
    @media (max-width: 991.98px) {
        /* هدر */
        .d-flex.justify-content-between.mb-3 {
            flex-direction: column;
            gap: 10px;
            align-items: stretch !important;
        }
        .d-flex.justify-content-between h4 {
            font-size: 1.02rem;
        }
        .d-flex.justify-content-between .small {
            font-size: 0.8rem;
            line-height: 1.6;
        }
        .d-flex.justify-content-between .btn {
            width: 100%;
            min-height: 44px;
        }

        /* جدول پاسخ‌ها */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
        .table {
            font-size: 0.82rem;
            min-width: 600px;
        }
        .table th,
        .table td {
            padding: 8px 5px !important;
        }
        .table td strong {
            font-size: 0.9rem;
        }
        .badge { font-size: 0.72rem !important; }
        .btn-sm {
            font-size: 0.78rem;
            padding: 6px 10px;
        }

        /* Modal */
        .modal-dialog {
            margin: 0.5rem;
        }
        .modal-body {
            padding: 12px 10px;
        }
        .modal-header {
            padding: 12px;
        }
        .modal-title {
            font-size: 1rem;
        }
        .modal-body h6 {
            font-size: 0.88rem;
            margin-top: 10px;
        }
        .modal-body .border.rounded.p-3 {
            padding: 10px !important;
            font-size: 0.92rem;
        }

        /* فرم تصحیح */
        .modal-body input[type="number"],
        .modal-body textarea {
            font-size: 1rem;
            min-height: 44px;
        }
        .modal-body button[type="submit"] {
            width: 100%;
            min-height: 48px;
            font-size: 1rem;
        }

        /* کد */
        .modal-body .code-block-wrapper pre[class*="language-"] {
            font-size: 0.78rem;
            max-height: 250px;
            -webkit-overflow-scrolling: touch;
        }

        /* دانلود فایل */
        .modal-body .btn-outline-primary {
            font-size: 0.9rem;
            padding: 8px 12px;
        }
    }
</style>
@endsection

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">پاسخ‌های تمرین: {{ $exercise->title }}</h4>
            <small class="text-muted">
                {{ $exercise->classSubject?->subject?->name }} |
                {{ $exercise->classSubject?->schoolClass?->name }} |
                حداکثر نمره: {{ $exercise->max_score }}
            </small>
        </div>
        <a href="{{ route($panelPrefix . '.exercises.index') }}" class="btn btn-secondary">بازگشت</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>دانش‌آموز</th>
                <th class="text-center">وضعیت ارسال</th>
                <th class="text-center">تاریخ ارسال</th>
                <th class="text-center">نمره</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                @php $sub = $submissions->get($student->id); @endphp
                <tr>
                    <td><strong>{{ $student->name }}</strong></td>
                    <td class="text-center">
                        @if(!$sub)
                            <span class="badge bg-secondary">ارسال نشده</span>
                        @elseif($sub->isGraded())
                            <span class="badge bg-success">تصحیح شده</span>
                        @else
                            <span class="badge bg-warning text-dark">در انتظار تصحیح</span>
                            @if($sub->is_late)
                                <span class="badge bg-danger">تأخیر</span>
                            @endif
                        @endif
                    </td>
                    <td class="text-center small">
                        {{ $sub?->submitted_at ? jdate($sub->submitted_at, 'yyyy/MM/dd HH:mm') : '—' }}
                    </td>
                    <td class="text-center">
                        @if($sub && $sub->isGraded())
                            <span class="badge bg-primary">{{ $sub->score }} / {{ $exercise->max_score }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($sub)
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#gradeModal{{ $sub->id }}">
                                مشاهده و تصحیح
                            </button>
                        @endif
                    </td>
                </tr>

                @if($sub)
                    {{-- Modal تصحیح --}}
                    <div class="modal fade" id="gradeModal{{ $sub->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">پاسخ {{ $student->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    @if($sub->content)
                                        <h6>متن پاسخ:</h6>
                                        <div class="border rounded p-3 mb-3 bg-light"
                                             style="text-align: right !important; direction: rtl; white-space: pre-wrap; word-break: break-word;">
                                            {{ $sub->content }}
                                        </div>
                                    @endif

                                    @if($sub->code_content)
                                        <h6>کد ارسال‌شده:</h6>
                                        <x-code-block :code="$sub->code_content" label="پاسخ کدنویسی دانش‌آموز" />
                                    @endif
                                    @if($sub->file_path)
                                        <p>
                                            <a href="{{ Storage::disk('public')->url($sub->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                📎 دانلود فایل پاسخ
                                            </a>
                                        </p>
                                    @endif

                                    <hr>

                                    <form action="{{ route($panelPrefix . '.exercises.grade', [$exercise, $sub]) }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label">نمره (از {{ $exercise->max_score }})</label>
                                            <input type="number" name="score" class="form-control" step="0.25"
                                                   min="0" max="{{ $exercise->max_score }}"
                                                   value="{{ old('score', $sub->score) }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">بازخورد (اختیاری)</label>
                                            <textarea name="feedback" class="form-control" rows="3">{{ old('feedback', $sub->feedback) }}</textarea>
                                        </div>
                                        <button class="btn btn-success">ثبت نمره</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <tr><td colspan="5" class="text-center text-muted">هیچ دانش‌آموزی در این کلاس نیست.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection
