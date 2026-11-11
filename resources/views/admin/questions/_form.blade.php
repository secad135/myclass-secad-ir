@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

@php
    $existingOptions = isset($question) ? $question->options->values() : collect([]);
    $existingCorrect = isset($question) ? $question->options->search(fn($o) => $o->is_correct) : 0;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">مبحث</label>
        <select name="topic_id" class="form-select" required>
            <option value="">— انتخاب مبحث —</option>
            @foreach($topics as $t)
                <option value="{{ $t->id }}" @selected(old('topic_id', $question->topic_id ?? $selectedTopic ?? '') == $t->id)>
                    {{ $t->subject?->name }} — {{ $t->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">سطح سختی</label>
        <select name="difficulty" class="form-select" required>
            <option value="easy"   @selected(old('difficulty', $question->difficulty ?? '') === 'easy')>آسان</option>
            <option value="medium" @selected(old('difficulty', $question->difficulty ?? 'medium') === 'medium')>متوسط</option>
            <option value="hard"   @selected(old('difficulty', $question->difficulty ?? '') === 'hard')>سخت</option>
        </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                   {{ old('is_active', $question->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">فعال</label>
        </div>
    </div>
</div>

<div class="mb-3 mt-3">
    <label class="form-label">متن سوال</label>
    <textarea name="text" class="form-control" rows="3" required>{{ old('text', $question->text ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">کد (اختیاری — اگر سوال شامل کد است)</label>
    <textarea name="code_snippet" class="form-control" rows="4" dir="ltr" style="font-family: monospace; font-size: 0.9rem;">{{ old('code_snippet', $question->code_snippet ?? '') }}</textarea>
    <small class="text-muted">این کد در آزمون زیر متن سوال به صورت جداگانه نمایش داده می‌شود.</small>
</div>

<hr>
<h6 class="mb-3">گزینه‌ها (دقیقاً ۴ گزینه) و انتخاب گزینه صحیح</h6>

@php
    $correctOld = old('correct_option', $existingCorrect);
@endphp

@for($i = 0; $i < 4; $i++)
    @php
        $optText = old("options.$i", $existingOptions->get($i)?->text ?? '');
    @endphp
    <div class="input-group mb-2">
        <div class="input-group-text">
            <input type="radio" name="correct_option" value="{{ $i }}"
                   @checked((int)$correctOld === $i) required>
        </div>
        <span class="input-group-text">{{ $i + 1 }}</span>
        <input type="text" name="options[{{ $i }}]" class="form-control"
               value="{{ $optText }}"
               placeholder="گزینه {{ $i + 1 }} (اگر کد است، از چپ شروع کنید)"
               dir="ltr"
               style="text-align: left; font-family: 'Courier New', monospace;"
               required>
        @if((int)$correctOld === $i)
            <span class="input-group-text bg-success text-white">✓ صحیح</span>
        @endif
    </div>
@endfor

<small class="text-muted d-block mt-2">
    روی دکمه رادیویی سمت راست هر گزینه بزنید تا آن را به‌عنوان «پاسخ صحیح» انتخاب کنید.
</small>