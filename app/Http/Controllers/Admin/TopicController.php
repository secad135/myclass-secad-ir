<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function index(Request $request)
    {
        $subjectId = $request->input('subject_id');
        $subjects  = Subject::orderBy('name')->get();

        $topics = Topic::with('subject')
            ->withCount('questions')
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->latest()
            ->get();

        return view('admin.topics.index', compact('topics', 'subjects', 'subjectId'));
    }

    public function create(Request $request)
    {
        $subjects = Subject::orderBy('name')->get();
        $selectedSubject = $request->input('subject_id');

        return view('admin.topics.create', compact('subjects', 'selectedSubject'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_id'  => 'required|exists:subjects,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        Topic::create($request->only('subject_id', 'name', 'description'));

        return redirect()->route('admin.topics.index', ['subject_id' => $request->subject_id])
            ->with('success', 'مبحث اضافه شد.');
    }

    public function edit(Topic $topic)
    {
        $subjects = Subject::orderBy('name')->get();
        return view('admin.topics.edit', compact('topic', 'subjects'));
    }

    public function update(Request $request, Topic $topic)
    {
        $request->validate([
            'subject_id'  => 'required|exists:subjects,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $topic->update($request->only('subject_id', 'name', 'description'));

        return redirect()->route('admin.topics.index')
            ->with('success', 'مبحث ویرایش شد.');
    }

    public function destroy(Topic $topic)
    {
        $topic->delete();
        return redirect()->route('admin.topics.index')
            ->with('success', 'مبحث حذف شد.');
    }
}