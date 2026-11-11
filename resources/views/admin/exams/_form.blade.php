@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

@php
    $classSubjectsJson = $classSubjects->map(fn($cs) => [
        'id'         => $cs->id,
        'subject_id' => $cs->subject_id,
    ])->toArray();

    $selectedTopics = old('topics', isset($exam) ? $exam->topics->pluck('id')->toArray() : []);
    $selectedCsId   = old('class_subject_id', $exam->class_subject_id ?? '');
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">کلاس-درس</label>
        <select name="class_subject_id" id="classSubjectSelect" class="form-select" required>
            <option value="">— انتخاب کنید —</option>
            @foreach($classSubjects as $cs)
                <option value="{{ $cs->id }}" @selected($selectedCsId == $cs->id)>
                    {{ $cs->subject?->name }} — {{ $cs->schoolClass?->name }} ({{ $cs->schoolClass?->school?->name }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">عنوان آزمون</label>
        <input type="text" name="title" class="form-control"
               value="{{ old('title', $exam->title ?? '') }}" required>
    </div>
</div>

<div class="mb-3 mt-3">
    <label class="form-label">توضیح (اختیاری)</label>
    <textarea name="description" class="form-control" rows="2">{{ old('description', $exam->description ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">مباحث (فقط مباحث درس انتخاب‌شده قابل انتخاب هستند)</label>
    <div id="topicsContainer" class="border rounded p-3 bg-light">
        <div class="text-muted small">ابتدا کلاس-درس را انتخاب کنید.</div>
        @foreach($topics->groupBy('subject_id') as $subjectId => $group)
            <div class="topic-group mb-2" data-subject-id="{{ $subjectId }}">
                <strong class="small text-muted d-block mb-1">{{ $group->first()->subject?->name }}</strong>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($group as $t)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="topics[]" value="{{ $t->id }}"
                                   id="topic_{{ $t->id }}" {{ in_array($t->id, (array) $selectedTopics) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="topic_{{ $t->id }}">
                                {{ $t->name }}
                                <span class="badge bg-info">{{ $t->questions_count ?? 0 }} سوال</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">تعداد سوال آزمون</label>
        <input type="number" name="question_count" class="form-control"
               value="{{ old('question_count', $exam->question_count ?? 20) }}" min="1" max="100" required>
        <small class="text-muted">به صورت تصادفی از مباحث انتخاب می‌شود.</small>
    </div>
    <div class="col-md-3">
        <label class="form-label">مدت آزمون (دقیقه)</label>
        <input type="number" name="duration_minutes" class="form-control"
               value="{{ old('duration_minutes', $exam->duration_minutes ?? 30) }}" min="1" max="300" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">حداکثر تلاش</label>
        <input type="number" name="max_attempts" class="form-control"
               value="{{ old('max_attempts', $exam->max_attempts ?? 2) }}" min="1" max="5" required>
        <small class="text-muted">پیشنهاد: ۲. بالاترین نمره ثبت می‌شود.</small>
    </div>
    <div class="col-md-3">
        <label class="form-label">فعال</label>
        <div class="form-check mt-2">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                   {{ old('is_active', $exam->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">آزمون فعال است</label>
        </div>
    </div>
</div>

<hr>

<h6 class="mb-2">بازه دسترسی (اختیاری)</h6>
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label small">تاریخ شروع</label>
        <input type="text" name="start_date" class="form-control form-control-sm"
               value="{{ old('start_date', isset($exam) && $exam->start_at ? jdate($exam->start_at) : '') }}"
               placeholder="1405/08/10" dir="ltr" data-jdp data-jdp-only-date>
    </div>
    <div class="col-md-3">
        <label class="form-label small">ساعت شروع</label>
        <input type="time" name="start_time" class="form-control form-control-sm" dir="ltr"
               value="{{ old('start_time', isset($exam) && $exam->start_at ? $exam->start_at->format('H:i') : '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small">تاریخ پایان</label>
        <input type="text" name="end_date" class="form-control form-control-sm"
               value="{{ old('end_date', isset($exam) && $exam->end_at ? jdate($exam->end_at) : '') }}"
               placeholder="1405/08/20" dir="ltr" data-jdp data-jdp-only-date>
    </div>
    <div class="col-md-3">
        <label class="form-label small">ساعت پایان</label>
        <input type="time" name="end_time" class="form-control form-control-sm" dir="ltr"
               value="{{ old('end_time', isset($exam) && $exam->end_at ? $exam->end_at->format('H:i') : '') }}">
    </div>
</div>
<small class="text-muted d-block mt-1">اگر خالی بگذارید، آزمون بدون محدودیت زمانی باز است (فقط با «فعال» کنترل می‌شود).</small>

<hr>

<h6 class="mb-2">تنظیمات نمایش</h6>
<div class="form-check">
    <input type="checkbox" name="shuffle_options" value="1" class="form-check-input" id="shuffle_options"
           {{ old('shuffle_options', $exam->shuffle_options ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="shuffle_options">ترتیب گزینه‌ها به صورت تصادفی نمایش داده شود</label>
</div>
<div class="form-check">
    <input type="checkbox" name="show_result_immediately" value="1" class="form-check-input" id="show_result_immediately"
           {{ old('show_result_immediately', $exam->show_result_immediately ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="show_result_immediately">نتیجه بلافاصله بعد از ثبت نمایش داده شود</label>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const classSubjectSelect = document.getElementById('classSubjectSelect');
    const topicsContainer = document.getElementById('topicsContainer');
    const classSubjectsData = @json($classSubjectsJson);

    function filterTopics() {
        const csId = parseInt(classSubjectSelect.value);
        if (!csId) {
            topicsContainer.querySelectorAll('.topic-group').forEach(g => g.style.display = 'none');
            topicsContainer.querySelector('.text-muted')?.style.setProperty('display', 'block');
            return;
        }
        topicsContainer.querySelector('.text-muted')?.style.setProperty('display', 'none');

        const cs = classSubjectsData.find(c => c.id === csId);
        if (!cs) return;

        topicsContainer.querySelectorAll('.topic-group').forEach(group => {
            const subId = parseInt(group.dataset.subjectId);
            const matches = subId === cs.subject_id;
            group.style.display = matches ? '' : 'none';
            if (!matches) {
                group.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            }
        });
    }

    classSubjectSelect.addEventListener('change', filterTopics);
    filterTopics(); // اجرای اولیه
});
</script>
@endpush
