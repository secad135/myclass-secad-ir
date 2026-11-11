<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\StudentReportService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
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

        $overall           = $service->getOverallStats($student, $fromDate, $toDate);
        $bySubject         = $service->getStatsByClassSubject($student, $fromDate, $toDate);
        $absences          = $service->getAbsenceDetails($student, $fromDate, $toDate);
        $groupedAttendance = $service->getGroupedAttendanceByClassSubject($student, $fromDate, $toDate);
        $status            = $service->getStatusLabel($overall['percentage']);

        return view('panel.attendance', compact(
            'student', 'currentYear',
            'fromDate', 'toDate', 'quickFilter',
            'overall', 'bySubject', 'absences', 'groupedAttendance', 'status'
        ));
    }
}