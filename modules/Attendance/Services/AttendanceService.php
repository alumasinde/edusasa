<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use App\Core\AuditLog;
use App\Core\BaseService;
use App\Core\Session;
use App\Core\ValidationException;
use Modules\Academic\Repositories\AcademicYearRepository;
use Modules\Academic\Repositories\TermRepository;
use Modules\Attendance\Repositories\AttendanceRepository;

class AttendanceService extends BaseService
{
    public function __construct(
        private readonly AttendanceRepository $attendance,
        private readonly AcademicYearRepository $years,
        private readonly TermRepository $terms,
    ) {}

    public function take(int $classId, ?int $streamId, string $date, array $entries): void
    {
        if (strtotime($date) > strtotime(date('Y-m-d'))) {
            throw new ValidationException(['date' => ['Attendance cannot be recorded for a future date.']]);
        }
        $validStatuses = ['present', 'absent', 'late', 'excused'];
        $records = [];
        foreach ($entries as $entry) {
            $status = $entry['status'] ?? 'present';
            if (!in_array($status, $validStatuses, true)) $status = 'present';
            $records[] = [
                'student_id' => (int) $entry['student_id'],
                'status' => $status,
                'remarks' => ($entry['remarks'] ?? '') !== '' ? trim($entry['remarks']) : null,
            ];
        }
        if ($records === []) {
            throw new ValidationException(['date' => ['No students found for that class — nothing to save.']]);
        }
        $currentYear = $this->years->current();
        $currentTerm = $this->terms->current();
        $this->attendance->saveBulk(
            $records, $classId, $streamId, $date,
            $currentYear !== null ? (int) $currentYear['id'] : null,
            $currentTerm !== null ? (int) $currentTerm['id'] : null,
            (int) Session::get('user_id'),
        );
        AuditLog::record('attendance.taken', 'attendance', $classId, null, [
            'date' => $date, 'class_id' => $classId, 'stream_id' => $streamId, 'count' => count($records),
        ]);
    }
}
