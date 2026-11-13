@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

<div class="mb-3">
    <label class="form-label">کلاس-درس</label>
    <select name="class_subject_id" class="form-select" required>
        <option value="">— انتخاب کنید —</option>
        @foreach($classSubjects as $cs)
            <option value="{{ $cs->id }}" @selected(old('class_subject_id', $exercise->class_subject_id ?? '') == $cs->id)>
                {{ $cs->subject?->name }} — {{ $cs->schoolClass?->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label">عنوان تمرین</label>
    <input type="text" name="title" class="form-control" value="{{ old('title', $exercise->title ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">متن تمرین</label>
    <textarea name="description" class="form-control" rows="6" required>{{ old('description', $exercise->description ?? '') }}</textarea>
    <small class="text-muted">توضیح کامل تکلیف برای دانش‌آموز.</small>
</div>

<div class="mb-3">
    <label class="form-label">فایل ضمیمه (اختیاری)</label>
    <input type="file" name="attachment" class="form-control">
    @if(isset($exercise) && $exercise->attachment_path)
        <small class="text-muted d-block mt-1">
            فایل فعلی:
            <a href="{{ URL::temporarySignedRoute('files.download', now()->addMinutes(60), ['path' => $exercise->attachment_path]) }}" target="_blank">دانلود</a>
        </small>
    @endif
</div>

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">حداکثر نمره</label>
        <input type="number" name="max_score" class="form-control" step="0.5" min="1" max="100"
               value="{{ old('max_score', $exercise->max_score ?? 20) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">تاریخ ددلاین</label>
        <input type="text" name="deadline_date" class="form-control"
               value="{{ old('deadline_date', isset($exercise) && $exercise->deadline_at ? jdate($exercise->deadline_at) : '') }}"
               placeholder="1405/08/20" dir="ltr" data-jdp data-jdp-only-date>
    </div>
    <div class="col-md-3">
        <label class="form-label">ساعت ددلاین</label>
        <input type="time" name="deadline_time" class="form-control"
               value="{{ old('deadline_time', isset($exercise) && $exercise->deadline_at ? $exercise->deadline_at->format('H:i') : '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">گزینه‌ها</label>
        <div class="form-check">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                   {{ old('is_active', $exercise->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="form-check-label">فعال</label>
        </div>
        <div class="form-check">
            <input type="checkbox" name="allow_late_submission" value="1" class="form-check-input" id="late"
                   {{ old('allow_late_submission', $exercise->allow_late_submission ?? false) ? 'checked' : '' }}>
            <label for="late" class="form-check-label">ارسال با تأخیر مجاز</label>
        </div>
    </div>
    <div class="col-md-3">
        <label class="form-label">نوع تمرین</label>

        {{-- requires_code --}}
        <input type="hidden" name="requires_code" value="0">
        <div class="form-check">
            <input type="checkbox" name="requires_code" value="1" class="form-check-input" id="requires_code"
                   {{ (bool) old('requires_code', $exercise->requires_code ?? false) ? 'checked' : '' }}>
            <label for="requires_code" class="form-check-label">تمرین کدنویسی (فیلد کد جدا)</label>
        </div>

        {{-- allow_file_upload --}}
        <input type="hidden" name="allow_file_upload" value="0">
        <div class="form-check">
            <input type="checkbox" name="allow_file_upload" value="1" class="form-check-input" id="allow_file_upload"
                   {{ (bool) old('allow_file_upload', $exercise->allow_file_upload ?? true) ? 'checked' : '' }}>
            <label for="allow_file_upload" class="form-check-label">اجازه آپلود فایل پاسخ</label>
        </div>
    </div>
</div>
