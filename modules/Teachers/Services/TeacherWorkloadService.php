<?php

declare(strict_types=1);

namespace Modules\Teachers\Services;

use App\Core\BaseService;
use App\Core\Tenant;
use Modules\Teachers\Repositories\TeacherSubjectRepository;

final class TeacherWorkloadService extends BaseService
{
    public function __construct(private readonly TeacherSubjectRepository $assignments) {}

    /** Return workload inputs used by the timetable engine, grouped per teacher. */
    public function matrix(?int $staffId = null): array
    {
        $rows = $staffId === null
            ? $this->db()->select(
                'SELECT ts.staff_id, CONCAT(s.first_name, " ", s.last_name) AS teacher_name,
                        ts.subject_id, sub.name AS subject_name, ts.class_id, c.name AS class_name,
                        ts.stream_id, str.name AS stream_name, COALESCE(ts.periods_per_week, 1) AS periods_per_week
                 FROM teacher_subjects ts
                 INNER JOIN staff s ON s.id = ts.staff_id AND s.school_id = :school_id AND s.deleted_at IS NULL
                 INNER JOIN subjects sub ON sub.id = ts.subject_id
                 LEFT JOIN classes c ON c.id = ts.class_id
                 LEFT JOIN streams str ON str.id = ts.stream_id
                 WHERE ts.school_id = :school_id
                 ORDER BY s.first_name, s.last_name, sub.name',
                ['school_id' => Tenant::id()]
            )
            : $this->db()->select(
                'SELECT ts.staff_id, CONCAT(s.first_name, " ", s.last_name) AS teacher_name,
                        ts.subject_id, sub.name AS subject_name, ts.class_id, c.name AS class_name,
                        ts.stream_id, str.name AS stream_name, COALESCE(ts.periods_per_week, 1) AS periods_per_week
                 FROM teacher_subjects ts
                 INNER JOIN staff s ON s.id = ts.staff_id AND s.school_id = :school_id AND s.deleted_at IS NULL
                 INNER JOIN subjects sub ON sub.id = ts.subject_id
                 LEFT JOIN classes c ON c.id = ts.class_id
                 LEFT JOIN streams str ON str.id = ts.stream_id
                 WHERE ts.school_id = :school_id AND ts.staff_id = :staff_id
                 ORDER BY sub.name',
                ['school_id' => Tenant::id(), 'staff_id' => $staffId]
            );

        $matrix = [];
        foreach ($rows as $row) {
            $id = (int) $row['staff_id'];
            if (!isset($matrix[$id])) {
                $matrix[$id] = [
                    'staff_id' => $id,
                    'teacher_name' => $row['teacher_name'],
                    'total_periods_per_week' => 0,
                    'assignments' => [],
                ];
            }
            $periods = max(0, (int) $row['periods_per_week']);
            $matrix[$id]['total_periods_per_week'] += $periods;
            $matrix[$id]['assignments'][] = [
                'subject_id' => (int) $row['subject_id'],
                'subject_name' => $row['subject_name'],
                'class_id' => $row['class_id'] !== null ? (int) $row['class_id'] : null,
                'class_name' => $row['class_name'],
                'stream_id' => $row['stream_id'] !== null ? (int) $row['stream_id'] : null,
                'stream_name' => $row['stream_name'],
                'periods_per_week' => $periods,
            ];
        }

        return array_values($matrix);
    }
}
