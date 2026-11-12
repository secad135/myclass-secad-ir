@php
$isTeacher = auth()->user()->is_teacher;
$panelPrefix = $isTeacher ? 'teacher' : 'admin';
$layout = $isTeacher ? 'layouts.teacher' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', 'حضور و غیاب — ' . $classSubject->subject->name)

@section('head')
    <script src="{{ asset('js/jalaali.min.js') }}"></script>
    <style>
        .session-cell { min-width: 130px; vertical-align: top; }
        .customized-badge { font-size: 0.65rem; }
        .is-customized { box-shadow: 0 0 0 2px #ffc107 inset; }
        .att-present   { background: #d4edda !important; }
        .att-absent    { background: #f8d7da !important; }
        .att-late      { background: #fff3cd !important; }
        .att-excused   { background: #d1ecf1 !important; }
        .att-left      { background: #e2e3e5 !important; }
        .att-cancelled { background: #e9ecef !important; color: #6c757d; }
        .student-col   { min-width: 130px; font-weight: 600; }

        /* ============ موبایل ============ */
        @media (max-width: 991.98px) {
            .attendance-table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 0 -16px;
                padding: 0 16px 8px;
            }
            .attendance-table-wrap .table {
                margin-bottom: 0;
            }

            /* ستون دانش‌آموز چسبیده در سمت راست */
            .student-col {
                position: sticky;
                right: 0;
                background: #fff !important;
                z-index: 2;
                box-shadow: -3px 0 6px rgba(0, 0, 0, 0.08);
                min-width: 120px;
                max-width: 120px;
                font-size: 0.9rem;
                padding: 10px 6px !important;
                word-break: break-word;
            }
            .student-col-header {
                position: sticky;
                right: 0;
                background: #f8f9fa !important;
                z-index: 3;
                box-shadow: -3px 0 6px rgba(0, 0, 0, 0.08);
                min-width: 120px;
                max-width: 120px;
            }

            /* سلول‌های جلسه بزرگ‌تر */
            .session-cell {
                min-width: 140px;
            }
            .session-select {
                font-size: 0.95rem !important;
                padding: 8px 6px !important;
                min-height: 42px;
            }
            .note-input {
                font-size: 0.85rem !important;
                padding: 8px 6px !important;
                min-height: 38px;
            }

            /* سر ستون‌ها */
            thead th {
                padding: 8px 4px !important;
                font-size: 0.85rem;
            }
            .cancel-session-btn {
                font-size: 0.7rem !important;
                padding: 3px 6px !important;
            }

            /* پنل روزهای ثبت‌شده */
            .recorded-days-table-wrap {
                max-height: 320px;
                overflow-y: auto;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            /* دکمه‌های اصلی */
            .btn-lg {
                font-size: 1rem !important;
                padding: 10px 18px !important;
                width: 100%;
            }
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 8px;
                width: 100%;
            }
            .action-buttons > * {
                width: 100%;
            }

            /* فرم امتیاز */
            .points-form-row > [class*="col-"] {
                width: 100% !important;
                margin-bottom: 10px;
            }
            .points-form-row .btn {
                min-height: 42px;
                font-size: 1rem;
            }

            /* تاریخ‌ها و توضیحات */
            .date-form-row {
                flex-direction: column;
                align-items: stretch !important;
            }
            .date-form-row .form-control,
            .date-form-row .btn {
                width: 100% !important;
                max-width: none !important;
                margin-bottom: 6px;
            }
        }
    </style>
@endsection

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">حضور و غیاب: {{ $classSubject->subject->name }}</h4>
            <small class="text-muted">
                کلاس: {{ $classSubject->schoolClass->name }} |
                مدرسه: {{ $classSubject->schoolClass->school?->name }} |
                تاریخ: <strong>{{ jdate($date) }}</strong> <small>({{ $date }})</small>
            </small>
        </div>
        <a href="{{ route($panelPrefix . '.classes.show', $classSubject->schoolClass) }}" class="btn btn-secondary">بازگشت</a>
    </div>

    <form action="{{ route($panelPrefix . '.attendance.show', $classSubject) }}" method="GET" class="mb-3" id="dateForm">
        <div class="d-flex gap-2 align-items-center flex-wrap date-form-row">
            <label class="form-label mb-0">تاریخ:</label>
            <input type="text" id="jalaliDateInput" name="date" class="form-control" value="{{ jdate($date) }}" data-jdp data-jdp-only-date placeholder="1403/08/10" style="max-width: 180px;" dir="ltr" autocomplete="off">
            <button type="submit" class="btn btn-outline-primary">برو</button>
            <a href="{{ route($panelPrefix . '.attendance.show', $classSubject) }}" class="btn btn-outline-secondary">امروز</a>
            <span class="text-muted small">({{ jdate($date, 'EEEE d MMMM yyyy') }})</span>
        </div>
    </form>

    @if($recordedDays->count() > 0)
        <details class="mb-3">
            <summary class="btn btn-sm btn-outline-dark">📅 روزهای ثبت‌شده ({{ $recordedDays->count() }})</summary>
            <div class="card card-body mt-2 recorded-days-table-wrap">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>تاریخ شمسی</th>
                            <th>میلادی</th>
                            <th>جلسات</th>
                            <th>وضعیت</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recordedDays as $rd)
                            @php $isCurrent = $rd->date->format('Y-m-d') === $date; @endphp
                            <tr class="{{ $isCurrent ? 'table-primary' : '' }}">
                                <td>{{ jdate($rd->date) }}</td>
                                <td class="text-muted small">{{ $rd->date->format('Y-m-d') }}</td>
                                <td>{{ $rd->sessions_count }} جلسه</td>
                                <td>
                                    @if($rd->is_started)
                                        <span class="badge bg-success">شروع شده</span>
                                    @else
                                        <span class="badge bg-secondary">شروع نشده</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isCurrent)
                                        <span class="badge bg-primary">در حال نمایش</span>
                                    @else
                                        <a href="{{ route($panelPrefix . '.attendance.show', ['classSubject' => $classSubject->id, 'date' => $rd->date->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-primary">مشاهده</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif

    @if(!$day)
        <div class="alert alert-info">
            <h5>روز {{ jdate($date) }} هنوز شروع نشده است.</h5>
            <p>با کلیک روی دکمه زیر، همه دانش‌آموزان به صورت «غایب» در همه جلسات ثبت می‌شوند.</p>
            <form action="{{ route($panelPrefix . '.attendance.start', $classSubject) }}" method="POST">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">

                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                @endif

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">تعداد جلسات این روز</label>
                        <select name="sessions_count" class="form-select">
                            @for($i = 1; $i <= 6; $i++)
                                <option value="{{ $i }}" @selected($i === $classSubject->subject->sessions_per_day)>{{ $i }} جلسه</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">شروع از زنگ</label>
                        <select name="start_period" class="form-select">
                            @for($i = 1; $i <= 4; $i++)
                                <option value="{{ $i }}" @selected($i === ($classSubject->default_start_period ?? 1))>زنگ {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <small class="text-muted d-block mb-3">
                    برای تدریس غیرحضوری، «۱ جلسه» را انتخاب کنید.
                </small>

                <button class="btn btn-success btn-lg">▶ شروع حضور و غیاب این روز</button>
            </form>
        </div>
    @else
        @if($students->isEmpty())
            <div class="alert alert-warning">هیچ دانش‌آموزی در این کلاس ثبت‌نام نشده است.</div>
        @else
            <div class="alert alert-secondary small mb-3">
                📌 این روز از <strong>زنگ {{ $day->start_period ?? 1 }}</strong>
                به مدت <strong>{{ $day->sessions_count ?? $sessionsCount }} جلسه</strong>
                برگزار می‌شود.
            </div>
            <form action="{{ route($panelPrefix . '.attendance.save', $classSubject) }}" method="POST" id="saveForm">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">

                <div class="attendance-table-wrap">
                    <table class="table table-bordered table-sm align-middle bg-white attendance-table">
                        <thead class="table-light">
                            <tr>
                                <th class="student-col student-col-header">دانش‌آموز</th>
                                @for($s = 1; $s <= $sessionsCount; $s++)
                                    <th class="text-center">
                                        جلسه {{ $s }}
                                        @if($s === 1)
                                            <br><small class="text-success">(مرجع)</small>
                                        @endif
                                        <br>
                                        <button type="button" class="btn btn-sm btn-outline-danger cancel-session-btn" data-session="{{ $s }}" title="همه را «برگزار نشد» کن">❌ برگزار نشد</button>
                                    </th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td class="student-col">
                                        {{ $student->name }}
                                        <a href="{{ route($panelPrefix . '.students.show', $student) }}" class="text-decoration-none small" title="وضعیت کلی">📋</a>
                                    </td>
                                    @for($s = 1; $s <= $sessionsCount; $s++)
                                        @php
                                            $att = $attendances[$student->id][$s] ?? null;
                                            $status = $att?->status ?? 'absent';
                                            $note = $att?->note ?? '';
                                            $customized = $att?->is_customized ?? false;
                                        @endphp
                                        <td class="session-cell att-{{ $status }} {{ $customized ? 'is-customized' : '' }}">
                                            <select name="attendances[{{ $student->id }}][{{ $s }}][status]" class="form-select form-select-sm session-select" data-student="{{ $student->id }}" data-session="{{ $s }}" data-customized="{{ $customized ? 'true' : 'false' }}">
                                                @foreach(\App\Models\Attendance::statuses() as $val => $label)
                                                    <option value="{{ $val }}" @selected($status === $val)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="attendances[{{ $student->id }}][{{ $s }}][customized]" value="{{ $customized ? 1 : 0 }}" class="customized-input">
                                            <input type="text" name="attendances[{{ $student->id }}][{{ $s }}][note]" value="{{ $note }}" class="form-control form-control-sm mt-1 note-input" placeholder="یادداشت..." style="font-size: 0.75rem;">
                                            @if($s > 1 && $customized)
                                                <span class="badge bg-warning text-dark customized-badge mt-1">دستی</span>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="action-buttons my-3">
                    <button class="btn btn-success btn-lg">💾 ذخیره همه</button>
                    <div class="text-muted small text-center">
                        جلسات ۲ تا {{ $sessionsCount }} به‌طور خودکار از جلسه قبلی تبعیت می‌کنند، تا وقتی دستی تغییرشان دهید.
                    </div>
                </div>
            </form>

            <div class="action-buttons mb-4">
                <form action="{{ route($panelPrefix . '.attendance.destroy-day', [$classSubject, $day]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" onclick="return confirm('همه اطلاعات این روز حذف شود؟')">🗑️ حذف این روز</button>
                </form>
            </div>
        @endif
    @endif

    @if($day)
        <hr class="my-4">

        <h5>امتیازات این روز — {{ jdate($date, 'EEEE d MMMM yyyy') }}</h5>

        <form action="{{ route($panelPrefix . '.attendance.points.add', $classSubject) }}" method="POST" class="row g-2 mb-3 align-items-end points-form-row">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <div class="col-md-3">
                <label class="form-label small">دانش‌آموز</label>
                <select name="student_id" class="form-select form-select-sm" required>
                    <option value="">— انتخاب —</option>
                    @foreach($students as $st)
                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">دسته</label>
                <select name="point_category_id" id="categorySelect" class="form-select form-select-sm">
                    <option value="">— بدون دسته —</option>
                    @foreach($pointCategories as $cat)
                        <option value="{{ $cat->id }}" data-points="{{ $cat->default_points }}">{{ $cat->name }} ({{ $cat->pointsLabel() }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">امتیاز</label>
                <input type="number" name="points" id="pointsInput" class="form-control form-control-sm" placeholder="2 یا -3" required min="-100" max="100" dir="ltr">
            </div>
            <div class="col-md-3">
                <label class="form-label small">توضیح (اختیاری)</label>
                <input type="text" name="note" class="form-control form-control-sm">
            </div>
            <div class="col-md-1">
                <button class="btn btn-success btn-sm w-100">➕</button>
            </div>
        </form>

        @if($pointEntries->count() > 0)
            <table class="table table-sm table-bordered align-middle bg-white">
                <thead class="table-light">
                    <tr>
                        <th>دانش‌آموز</th>
                        <th>دسته</th>
                        <th>امتیاز</th>
                        <th>توضیح</th>
                        <th>ثبت‌کننده</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pointEntries as $entry)
                        <tr>
                            <td>{{ $entry->student?->name ?? '—' }}</td>
                            <td>
                                @if($entry->category)
                                    <span class="badge bg-{{ $entry->category->color() }}">{{ $entry->category->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><span class="badge bg-{{ $entry->points >= 0 ? 'success' : 'danger' }}">{{ $entry->points > 0 ? '+' : '' }}{{ $entry->points }}</span></td>
                            <td><small>{{ $entry->note }}</small></td>
                            <td><small class="text-muted">{{ $entry->createdBy?->name }}</small></td>
                            <td>
                                @if(!$isTeacher || (int) $entry->created_by === (int) auth()->id())
                                    <form action="{{ route($panelPrefix . '.attendance.points.delete', [$classSubject, $entry]) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف شود؟')">×</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="alert alert-light border small">برای این روز هنوز امتیازی ثبت نشده است.</div>
        @endif
    @endif

@endsection

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // ۱. تغییر رنگ سلول
        function updateColor(select) {
            const cell = select.closest('.session-cell');
            if (!cell) return;
            cell.className = cell.className.replace(/att-\w+/g, '');
            cell.classList.add('att-' + select.value, 'session-cell');
            if (select.dataset.customized === 'true') {
                cell.classList.add('is-customized');
            }
        }

        // ۲. علامت‌گذاری دستی
        function markCustomized(select) {
            select.dataset.customized = 'true';
            const cell = select.closest('.session-cell');
            if (!cell) return;
            cell.classList.add('is-customized');
            const hidden = cell.querySelector('.customized-input');
            if (hidden) hidden.value = '1';
            if (!cell.querySelector('.customized-badge')) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-warning text-dark customized-badge mt-1';
                badge.textContent = 'دستی';
                cell.appendChild(badge);
            }
        }

        // ۳. کپی به جلسات بعدی
        function propagateForward(studentId, fromSession, value) {
            if (value === 'cancelled') return;
            let next = fromSession + 1;
            while (true) {
                const nextSelect = document.querySelector(`.session-select[data-student="${studentId}"][data-session="${next}"]`);
                if (!nextSelect) break;
                // اگر سلول بعدی قبلاً دستی تغییر کرده بود، کپی نکن
                if (nextSelect.dataset.customized === 'true') break; 
                if (nextSelect.disabled) break;
                nextSelect.value = value;
                updateColor(nextSelect);
                next++;
            }
        }

        // ۴. اتصال رویداد به همه سلول‌ها
        document.querySelectorAll('.session-select').forEach(function (select) {
            select.addEventListener('change', function () {
                const studentId = this.dataset.student;
                const session = parseInt(this.dataset.session);
                const value = this.value;

                // اگر جلسه دوم به بعد بود، دستی حساب کن
                if (session > 1) {
                    markCustomized(this);
                }
                updateColor(this);
                // مقدار جلسه فعلی را به بعدی‌ها سرریز کن
                propagateForward(studentId, session, value);
            });
        });

        // ۵. دکمه برگزار نشد
        document.querySelectorAll('.cancel-session-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const session = this.dataset.session;
                if (!confirm(`آیا همه دانش‌آموزان در جلسه ${session} «برگزار نشد» ثبت شوند؟`)) return;
                document.querySelectorAll(`.session-select[data-session="${session}"]`).forEach(function (sel) {
                    sel.value = 'cancelled';
                    sel.dataset.customized = 'true';
                    const hidden = sel.closest('.session-cell')?.querySelector('.customized-input');
                    if (hidden) hidden.value = '1';
                    updateColor(sel);
                });
            });
        });

        // ۶. پر کردن خودکار امتیاز
        const categorySelect = document.getElementById('categorySelect');
        const pointsInput = document.getElementById('pointsInput');
        if (categorySelect && pointsInput) {
            categorySelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                if (opt.dataset.points !== undefined && opt.dataset.points !== '') {
                    pointsInput.value = opt.dataset.points;
                }
            });
        }
    });
    </script>
@endpush
