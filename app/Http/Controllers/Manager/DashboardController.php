<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;

class DashboardController extends Controller
{
    public function index()
    {
        $manager = auth()->user();

        // فقط کلاس‌های مدرسه‌ای که مسئول آن است را می‌بیند
        $classes = SchoolClass::with(['academicYear', 'grade', 'teacher'])
            ->withCount('students')
            ->where('school_id', $manager->school_id)
            ->latest()
            ->get();

        return view('manager.dashboard', compact('classes'));
    }
}
