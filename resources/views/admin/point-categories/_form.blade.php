@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

<div class="mb-3">
    <label class="form-label">نام دسته</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $pointCategory->name ?? '') }}" placeholder="مثلاً تکلیف انجام شده">
</div>
<div class="mb-3">
    <label class="form-label">امتیاز پیش‌فرض (می‌تواند منفی باشد)</label>
    <input type="number" name="default_points" class="form-control" dir="ltr" value="{{ old('default_points', $pointCategory->default_points ?? '') }}" min="-100" max="100" step="1">
    <small class="text-muted">مثبت = پاداش، منفی = جریمه. مثلاً: +2، -3</small>
</div>
<div class="mb-3">
    <label class="form-label">توضیح (اختیاری)</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $pointCategory->description ?? '') }}</textarea>
</div>
<div class="mb-3 form-check">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $pointCategory->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">فعال (در فرم ثبت امتیاز نمایش داده شود)</label>
</div>