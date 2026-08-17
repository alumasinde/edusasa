<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use Modules\Academic\Repositories\SchoolClassRepository;
use Modules\Academic\Repositories\StreamRepository;
use Modules\Attendance\Repositories\AttendanceRepository;
use Modules\Attendance\Services\AttendanceService;

class AttendanceController extends BaseController
{
    public function __construct(
        private readonly AttendanceRepository $attendance,
        private readonly AttendanceService $service,
        private readonly SchoolClassRepository $classes,
        private readonly StreamRepository $streams,
    ) {}

    public function index(Request $request): Response
    {
        return $this->view('attendance.index', ['classes' => $this->classes->all('sequence ASC')]);
    }

    public function take(Request $request): Response
    {
        $classId = (int) $request->input('class_id', 0);
        $streamId = $request->input('stream_id', '') !== '' ? (int) $request->input('stream_id') : null;
        $date = (string) $request->input('date', date('Y-m-d'));
        if ($classId === 0) return $this->redirect('/attendance');
        return $this->view('attendance.take', [
            'roster' => $this->attendance->rosterFor($classId, $streamId, $date),
            'class' => $this->classes->find($classId), 'classId' => $classId, 'streamId' => $streamId,
            'streams' => $this->streams->forClass($classId), 'date' => $date, 'errors' => [],
        ]);
    }

    public function store(Request $request): Response
    {
        $classId = (int) $request->input('class_id', 0);
        $streamId = $request->input('stream_id', '') !== '' ? (int) $request->input('stream_id') : null;
        $date = (string) $request->input('date', date('Y-m-d'));
        $studentIds = (array) $request->input('student_id', []);
        $statuses = (array) $request->input('status', []);
        $remarks = (array) $request->input('remarks', []);
        $entries = [];
        foreach ($studentIds as $i => $studentId) {
            $entries[] = ['student_id' => $studentId, 'status' => $statuses[$i] ?? 'present', 'remarks' => $remarks[$i] ?? ''];
        }
        try {
            $this->service->take($classId, $streamId, $date, $entries);
        } catch (ValidationException $e) {
            return $this->view('attendance.take', [
                'roster' => $this->attendance->rosterFor($classId, $streamId, $date),
                'class' => $this->classes->find($classId), 'classId' => $classId, 'streamId' => $streamId,
                'streams' => $this->streams->forClass($classId), 'date' => $date, 'errors' => $e->errors(),
            ], 422);
        }
        Session::flash('success', 'Attendance saved for ' . $date . '.');
        return $this->redirect('/attendance/take?class_id=' . $classId . ($streamId !== null ? '&stream_id=' . $streamId : '') . '&date=' . $date);
    }

    public function report(Request $request): Response
    {
        $classId = (int) $request->input('class_id', 0);
        $streamId = $request->input('stream_id', '') !== '' ? (int) $request->input('stream_id') : null;
        $startDate = (string) $request->input('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = (string) $request->input('end_date', date('Y-m-d'));
        return $this->view('attendance.report', [
            'classes' => $this->classes->all('sequence ASC'), 'streams' => $classId > 0 ? $this->streams->forClass($classId) : [],
            'summary' => $classId > 0 ? $this->attendance->classSummary($classId, $streamId, $startDate, $endDate) : [],
            'classId' => $classId, 'streamId' => $streamId, 'startDate' => $startDate, 'endDate' => $endDate,
        ]);
    }
}
