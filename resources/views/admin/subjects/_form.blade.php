@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

<div class="mb-3">
    <label class="form-label">نام درس</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $subject->name ?? '') }}" placeholder="مثلاً کارگاه PHP MySQL">
</div>
<div class="mb-3">
    <label class="form-label">کد درس (اختیاری)</label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $subject->code ?? '') }}" dir="ltr">
</div>
<div class="mb-3">
    <label class="form-label">نوع درس</label>
    <select name="type" class="form-select">
        <option value="workshop" {{ old('type', $subject->type ?? '') == 'workshop' ? 'selected' : '' }}>کارگاه</option>
        <option value="regular" {{ old('type', $subject->type ?? 'regular') == 'regular' ? 'selected' : '' }}>عادی</option>
    </select>
</div>
<div class="mb-3">
    <label class="form-label">تعداد جلسه در یک روز</label>
    <select name="sessions_per_day" class="form-select">
        @for($i = 1; $i <= 4; $i++)
            <option value="{{ $i }}" {{ old('sessions_per_day', $subject->sessions_per_day ?? 1) == $i ? 'selected' : '' }}>{{ $i }} جلسه</option>
        @endfor
    </select>
</div>
<div class="mb-3">
    <label class="form-label">روزهای هفته</label>
    <div class="d-flex flex-wrap gap-3">
        @foreach(\App\Models\Subject::dayNames() as $val => $label)
            @php
                $current = old('default_days', $subject->default_days ?? []);
                $checked = in_array($val, (array) $current);
            @endphp
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="default_days[]" value="{{ $val }}" id="day_{{ $val }}" {{ $checked ? 'checked' : '' }}>
                <label class="form-check-label" for="day_{{ $val }}">{{ $label }}</label>
            </div>
        @endforeach
    </div>
</div>