<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index()
    {
        $grades = Grade::latest()->get();
        return view('admin.grades.index', compact('grades'));
    }

    public function create()
    {
        return view('admin.grades.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'field' => 'nullable|string|max:255',
        ]);

        Grade::create([
            'name'  => $request->name,
            'field' => $request->field,
        ]);

        return redirect()->route('admin.grades.index')
                         ->with('success', 'پایه تحصیلی اضافه شد.');
    }

    public function edit(Grade $grade)
    {
        return view('admin.grades.edit', compact('grade'));
    }

    public function update(Request $request, Grade $grade)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'field' => 'nullable|string|max:255',
        ]);

        $grade->update([
            'name'  => $request->name,
            'field' => $request->field,
        ]);

        return redirect()->route('admin.grades.index')
                         ->with('success', 'پایه تحصیلی ویرایش شد.');
    }

    public function destroy(Grade $grade)
    {
        $grade->delete();

        return redirect()->route('admin.grades.index')
                         ->with('success', 'پایه تحصیلی حذف شد.');
    }
}