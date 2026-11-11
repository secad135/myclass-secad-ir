<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index()
    {
        $years = AcademicYear::latest()->get();
        return view('admin.academic-years.index', compact('years'));
    }

    public function create()
    {
        return view('admin.academic-years.create');
    }

    public function store(Request $request)
    {
        // در AcademicYearController::store و update، ابتدا این دو خط را اضافه کنید:
        $request->merge([
            'start_date' => $request->start_date ? to_gregorian($request->start_date) : null,
            'end_date'   => $request->end_date   ? to_gregorian($request->end_date)   : null,
        ]);
        $request->validate([
            'title'      => 'required|string|max:255|unique:academic_years,title',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'is_current' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_current')) {
            AcademicYear::where('is_current', true)->update(['is_current' => false]);
        }

        AcademicYear::create([
            'title'      => $request->title,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'is_current' => $request->boolean('is_current'),
        ]);

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'سال تحصیلی اضافه شد.');
    }

    public function edit(AcademicYear $academicYear)
    {
        return view('admin.academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        // در AcademicYearController::store و update، ابتدا این دو خط را اضافه کنید:
        $request->merge([
            'start_date' => $request->start_date ? to_gregorian($request->start_date) : null,
            'end_date'   => $request->end_date   ? to_gregorian($request->end_date)   : null,
        ]);
        $request->validate([
            'title'      => 'required|string|max:255|unique:academic_years,title,' . $academicYear->id,
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'is_current' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_current')) {
            AcademicYear::where('is_current', true)
                ->where('id', '!=', $academicYear->id)
                ->update(['is_current' => false]);
        }

        $academicYear->update([
            'title'      => $request->title,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'is_current' => $request->boolean('is_current'),
        ]);

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'سال تحصیلی ویرایش شد.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'سال تحصیلی حذف شد.');
    }
}
