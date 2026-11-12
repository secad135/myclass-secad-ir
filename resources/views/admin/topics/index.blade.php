@php
$isTeacher = auth()->user()->is_teacher;
$panelPrefix = $isTeacher ? 'teacher' : 'admin';
$layout = $isTeacher ? 'layouts.teacher' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', $isTeacher ? 'مباحث من' : 'مباحث')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ $isTeacher ? 'مباحث من' : 'مباحث' }}</h4>
        <a href="{{ route($panelPrefix . '.topics.create', ['subject_id' => $subjectId]) }}" class="btn btn-primary">➕ افزودن مبحث</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">فیلتر بر اساس درس</label>
                    <select name="subject_id" class="form-select" onchange="this.form.submit()">
                        <option value="">— همه دروس —</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" @selected($subjectId == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>نام مبحث</th>
                <th>درس</th>
                <th>توضیح</th>
                <th class="text-center">تعداد سوال</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topics as $topic)
                <tr>
                    <td>{{ $topic->id }}</td>
                    <td><strong>{{ $topic->name }}</strong></td>
                    <td><small>{{ $topic->subject?->name ?? '—' }}</small></td>
                    <td><small class="text-muted">{{ Str::limit($topic->description, 60) }}</small></td>
                    <td class="text-center">
                        <a href="{{ route($panelPrefix . '.questions.index', ['topic_id' => $topic->id]) }}" class="badge bg-info text-decoration-none">
                            {{ $topic->questions_count }} سوال
                        </a>
                    </td>
                    <td>
                        <a href="{{ route($panelPrefix . '.topics.edit', $topic) }}" class="btn btn-sm btn-warning">ویرایش</a>
                        <form action="{{ route($panelPrefix . '.topics.destroy', $topic) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('حذف شود؟ سوالات این مبحث هم حذف می‌شوند.')">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">هیچ مبحثی ثبت نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>

@endsection
