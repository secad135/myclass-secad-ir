<?php

namespace App\Http\Controllers\Teacher;

use App\Models\Subject;
use App\Models\Topic;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class TopicController extends BaseController
{
    public function index(Request $request)
    {
        $subjectIds = $this->ownSubjectIds();
        $subjectId  = $request->input('subject_id');

        if ($subjectId) {
            $this->assertOwnsSubject((int) $subjectId);
        }

        $subjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();

        $topics = Topic::with('subject')
            ->withCount('questions')
            ->whereIn('subject_id', $subjectIds)
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->latest()
            ->get();

        return view('admin.topics.index', compact('topics', 'subjects', 'subjectId'));
    }

    public function create(Request $request)
    {
        $subjects = Subject::whereIn('id', $this->ownSubjectIds())->orderBy('name')->get();
        $selectedSubject = $request->input('subject_id');

        if ($selectedSubject) {
            $this->assertOwnsSubject((int) $selectedSubject);
        }

        return view('admin.topics.create', compact('subjects', 'selectedSubject'));
    }

    public function store(Request $request)
    {
        $data = $this->validateTopic($request);
        $this->assertOwnsSubject((int) $data['subject_id']);

        Topic::create($data);

        ActivityLogger::log(auth()->user(), 'topic_create', 'مبحث «' . $data['name'] . '» را ساخت.');

        return redirect()->route('teacher.topics.index', ['subject_id' => $data['subject_id']])
            ->with('success', 'مبحث اضافه شد.');
    }

    public function edit(Topic $topic)
    {
        $this->assertOwnsTopic($topic);

        $subjects = Subject::whereIn('id', $this->ownSubjectIds())->orderBy('name')->get();

        return view('admin.topics.edit', compact('topic', 'subjects'));
    }

    public function update(Request $request, Topic $topic)
    {
        $this->assertOwnsTopic($topic);

        $data = $this->validateTopic($request);
        $this->assertOwnsSubject((int) $data['subject_id']);

        $topic->update($data);

        ActivityLogger::log(auth()->user(), 'topic_update', 'مبحث «' . $data['name'] . '» را ویرایش کرد.');

        return redirect()->route('teacher.topics.index')
            ->with('success', 'مبحث ویرایش شد.');
    }

    public function destroy(Topic $topic)
    {
        $this->assertOwnsTopic($topic);

        $name = $topic->name;
        $topic->delete();

        ActivityLogger::log(auth()->user(), 'topic_delete', 'مبحث «' . $name . '» را حذف کرد.');

        return redirect()->route('teacher.topics.index')
            ->with('success', 'مبحث حذف شد.');
    }

    private function validateTopic(Request $request): array
    {
        return $request->validate([
            'subject_id'  => 'required|exists:subjects,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
    }
}
