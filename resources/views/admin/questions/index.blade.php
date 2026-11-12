@php
$isTeacher = auth()->user()->is_teacher;
$panelPrefix = $isTeacher ? 'teacher' : 'admin';
$layout = $isTeacher ? 'layouts.teacher' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', $isTeacher ? 'بانک سوالات من' : 'بانک سوالات')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ $isTeacher ? 'بانک سوالات من' : 'بانک سوالات' }}</h4>
    <a href="{{ route($panelPrefix . '.questions.create', ['topic_id' => $topicId]) }}" class="btn btn-primary">➕ افزودن سوال</a>
    <a href="{{ route($panelPrefix . '.questions.import') }}" class="btn btn-outline-success">📥 ورود گروهی (JSON)</a>

</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small">فیلتر بر اساس مبحث</label>
                <select name="topic_id" class="form-select" onchange="this.form.submit()">
                    <option value="">— همه مباحث —</option>
                    @foreach($topics as $t)
                    <option value="{{ $t->id }}" @selected($topicId==$t->id)>
                        {{ $t->subject?->name }} — {{ $t->name }}
                    </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

@forelse($questions as $question)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <span class="badge bg-secondary">#{{ $question->id }}</span>
            <span class="badge bg-info">{{ $question->topic?->subject?->name }}</span>
            <span class="badge bg-primary">{{ $question->topic?->name }}</span>
            <span class="badge bg-{{ $question->difficultyColor() }}">{{ $question->difficultyLabel() }}</span>
            @if(!$question->is_active)
            <span class="badge bg-secondary">غیرفعال</span>
            @endif
        </div>
        <div>
            <a href="{{ route($panelPrefix . '.questions.edit', $question) }}" class="btn btn-sm btn-warning">ویرایش</a>
            <form action="{{ route($panelPrefix . '.questions.destroy', $question) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟')">حذف</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <p class="mb-2"><strong>سوال:</strong> {!! nl2br(e($question->text)) !!}</p>
        @if($question->code_snippet)
            <x-code-block :code="$question->code_snippet" label="کد" />
        @endif
        <div class="mt-2">
            <strong class="small">گزینه‌ها:</strong>
            <div class="row g-1 mt-1">
                @foreach($question->options as $i => $opt)
                <div class="col-md-6">
                    <div class="border rounded p-2 small {{ $opt->is_correct ? 'border-success bg-success-subtle' : '' }}">
                        <span class="badge bg-{{ $opt->is_correct ? 'success' : 'secondary' }}">{{ $i + 1 }}</span>
                        {{ $opt->text }}
                        @if($opt->is_correct)
                        <span class="text-success">✓</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@empty
<div class="alert alert-info">هیچ سوالی ثبت نشده است.</div>
@endforelse

{{ $questions->links() }}

@endsection
