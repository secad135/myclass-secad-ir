<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\StudentReportService;
use Illuminate\Http\Request;

class PointsController extends Controller
{
    public function index(Request $request)
    {
        $student     = auth()->user();
        $currentYear = AcademicYear::where('is_current', true)->first();

        if (!$currentYear) {
            return redirect()->route('panel.dashboard')
                ->with('error', 'سال تحصیلی جاری تعریف نشده است.');
        }

        $service = new StudentReportService();

        $quickFilter = $request->input('quick', 'all');
        $fromInput   = $request->input('from');
        $toInput     = $request->input('to');

        [$fromDate, $toDate] = $service->resolveDateRange($fromInput, $toInput, $quickFilter, $currentYear);

        $points = $service->getPointsSummary($student, $fromDate, $toDate);

        return view('panel.points', compact(
            'student', 'currentYear',
            'fromDate', 'toDate', 'quickFilter',
            'points'
        ));
    }
}