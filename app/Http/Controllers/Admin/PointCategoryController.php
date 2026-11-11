<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointCategory;
use Illuminate\Http\Request;

class PointCategoryController extends Controller
{
    public function index()
    {
        $categories = PointCategory::latest()
            ->get();

        return view('admin.point-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.point-categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'default_points' => 'required|integer|between:-100,100',
            'description'    => 'nullable|string|max:1000',
            'is_active'      => 'nullable|boolean',
        ]);

        PointCategory::create([
            'name'           => $request->name,
            'default_points' => $request->default_points,
            'description'    => $request->description,
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.point-categories.index')
                         ->with('success', 'دسته امتیاز اضافه شد.');
    }

    public function edit(PointCategory $pointCategory)
    {
        return view('admin.point-categories.edit', compact('pointCategory'));
    }

    public function update(Request $request, PointCategory $pointCategory)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'default_points' => 'required|integer|between:-100,100',
            'description'    => 'nullable|string|max:1000',
            'is_active'      => 'nullable|boolean',
        ]);

        $pointCategory->update([
            'name'           => $request->name,
            'default_points' => $request->default_points,
            'description'    => $request->description,
            'is_active'      => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.point-categories.index')
                         ->with('success', 'دسته امتیاز ویرایش شد.');
    }

    public function destroy(PointCategory $pointCategory)
    {
        $pointCategory->delete();
        return redirect()->route('admin.point-categories.index')
                         ->with('success', 'دسته امتیاز حذف شد.');
    }
}