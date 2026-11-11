<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::latest()->get();
        return view('admin.subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('admin.subjects.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'code'              => 'nullable|string|max:255|unique:subjects,code',
            'type'              => 'required|in:workshop,regular',
            'sessions_per_day'  => 'required|integer|in:1,2,3,4',
            'default_days'      => 'nullable|array',
            'default_days.*'    => 'integer|between:0,6',
        ]);

        Subject::create([
            'name'             => $request->name,
            'code'             => $request->code,
            'type'             => $request->type,
            'sessions_per_day' => $request->sessions_per_day,
            'default_days'     => $request->default_days ?? [],
        ]);

        return redirect()->route('admin.subjects.index')
                         ->with('success', 'درس اضافه شد.');
    }

    public function edit(Subject $subject)
    {
        return view('admin.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'code'              => 'nullable|string|max:255|unique:subjects,code,' . $subject->id,
            'type'              => 'required|in:workshop,regular',
            'sessions_per_day'  => 'required|integer|in:1,2,3,4',
            'default_days'      => 'nullable|array',
            'default_days.*'    => 'integer|between:0,6',
        ]);

        $subject->update([
            'name'             => $request->name,
            'code'             => $request->code,
            'type'             => $request->type,
            'sessions_per_day' => $request->sessions_per_day,
            'default_days'     => $request->default_days ?? [],
        ]);

        return redirect()->route('admin.subjects.index')
                         ->with('success', 'درس ویرایش شد.');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('admin.subjects.index')
                         ->with('success', 'درس حذف شد.');
    }
}