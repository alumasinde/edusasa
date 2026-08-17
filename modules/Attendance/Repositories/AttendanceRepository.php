<?php

declare(strict_types=1);

namespace Modules\Attendance\Repositories;

use App\Core\BaseRepository;
use App\Core\Tenant;

class AttendanceRepository extends BaseRepository
{
    protected function table(): string { return 'attendance'; }

    public function rosterFor(int $classId, ?int $streamId, string $date): array
    {
        $sql = "SELECT s.id AS student_id, s.admission_no, s.first_name, s.last_name,
                    a.id AS attendance_id, a.status, a.remarks
                FROM students s
                LEFT JOIN attendance a ON a.student_id = s.id AND a.attendance_date = :date AND a.deleted_at IS NULL
                WHERE s.school_id = :school_id AND s.deleted_at IS NULL
                AND s.status = 'active' AND s.current_class_id = :class_id";
        $params = ['school_id' => Tenant::id(), 'class_id' => $classId, 'date' => $date];
        if ($streamId !== null) { $sql .= ' AND s.current_stream_id = :stream_id'; $params['stream_id'] = $streamId; }
        $sql .= ' ORDER BY s.first_name ASC, s.last_name ASC';
        return $this->db->select($sql, $params);
    }

    public function saveBulk(array $records, int $classId, ?int $streamId, string $date, ?int $academicYearId, ?int $termId, ?int $recordedBy): void
    {
        $schoolId = Tenant::id();
        $this->db->transaction(function () use ($records, $classId, $streamId, $date, $academicYearId, $termId, $recordedBy, $schoolId) {
            foreach ($records as $record) {
                $this->db->execute(
                    'INSERT INTO attendance
                        (school_id, student_id, class_id, stream_id, academic_year_id, term_id, attendance_date, status, remarks, recorded_by)
                     VALUES
                        (:school_id, :student_id, :class_id, :stream_id, :academic_year_id, :term_id, :attendance_date, :status, :remarks, :recorded_by)
                     ON DUPLICATE KEY UPDATE
                        status = VALUES(status), remarks = VALUES(remarks), stream_id = VALUES(stream_id),
                        academic_year_id = VALUES(academic_year_id), term_id = VALUES(term_id), recorded_by = VALUES(recorded_by), deleted_at = NULL',
                    [
                        'school_id' => $schoolId, 'student_id' => $record['student_id'], 'class_id' => $classId,
                        'stream_id' => $streamId, 'academic_year_id' => $academicYearId, 'term_id' => $termId,
                        'attendance_date' => $date, 'status' => $record['status'], 'remarks' => $record['remarks'], 'recorded_by' => $recordedBy,
                    ]
                );
            }
        });
    }

    public function historyForStudent(int $studentId, int $limit = 30): array
    {
        return $this->db->select('SELECT * FROM attendance WHERE student_id = :student_id AND deleted_at IS NULL ORDER BY attendance_date DESC LIMIT ' . (int) $limit, ['student_id' => $studentId]);
    }

    public function summaryForStudent(int $studentId): array
    {
        $row = $this->db->selectOne("SELECT SUM(status = 'present') AS present, SUM(status = 'absent') AS absent, SUM(status = 'late') AS late, SUM(status = 'excused') AS excused, COUNT(*) AS total FROM attendance WHERE student_id = :student_id AND deleted_at IS NULL", ['student_id' => $studentId]);
        return [
            'present' => (int) ($row['present'] ?? 0), 'absent' => (int) ($row['absent'] ?? 0),
            'late' => (int) ($row['late'] ?? 0), 'excused' => (int) ($row['excused'] ?? 0), 'total' => (int) ($row['total'] ?? 0),
        ];
    }

    public function classSummary(int $classId, ?int $streamId, string $startDate, string $endDate): array
    {
        $sql = "SELECT s.id AS student_id, s.admission_no, s.first_name, s.last_name,
                    SUM(a.status = 'present') AS present, SUM(a.status = 'absent') AS absent,
                    SUM(a.status = 'late') AS late, SUM(a.status = 'excused') AS excused, COUNT(a.id) AS marked_days
                FROM students s
                LEFT JOIN attendance a ON a.student_id = s.id AND a.attendance_date BETWEEN :start_date AND :end_date AND a.deleted_at IS NULL
                WHERE s.school_id = :school_id AND s.deleted_at IS NULL AND s.status = 'active' AND s.current_class_id = :class_id";
        $params = ['school_id' => Tenant::id(), 'class_id' => $classId, 'start_date' => $startDate, 'end_date' => $endDate];
        if ($streamId !== null) { $sql .= ' AND s.current_stream_id = :stream_id'; $params['stream_id'] = $streamId; }
        $sql .= ' GROUP BY s.id ORDER BY s.first_name ASC, s.last_name ASC';
        return $this->db->select($sql, $params);
    }
}
