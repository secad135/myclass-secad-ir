<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::latest()->get();
        return view('admin.schools.index', compact('schools'));
    }

    public function create()
    {
        return view('admin.schools.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'code'    => 'nullable|string|max:255|unique:schools,code',
            'address' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
        ]);

        School::create($request->only('name', 'code', 'address', 'phone'));

        return redirect()->route('admin.schools.index')
                         ->with('success', 'مدرسه اضافه شد.');
    }

    public function edit(School $school)
    {
        return view('admin.schools.edit', compact('school'));
    }

    public function update(Request $request, School $school)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'code'    => 'nullable|string|max:255|unique:schools,code,' . $school->id,
            'address' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
        ]);

        $school->update($request->only('name', 'code', 'address', 'phone'));

        return redirect()->route('admin.schools.index')
                         ->with('success', 'مدرسه ویرایش شد.');
    }

    public function destroy(School $school)
    {
        $school->delete();
        return redirect()->route('admin.schools.index')
                         ->with('success', 'مدرسه حذف شد.');
    }
}