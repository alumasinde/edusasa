<?php

declare(strict_types=1);

namespace Modules\Teachers\Repositories;

use App\Core\BaseRepository;
use App\Core\Tenant;

class TeacherSubjectRepository extends BaseRepository
{
    protected function table(): string { return 'teacher_subjects'; }
    protected function tenantScoped(): bool { return true; }

    public function find(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM teacher_subjects WHERE id = :id AND school_id = :school_id', ['id' => $id, 'school_id' => Tenant::id()]);
    }

    public function delete(int $id): int
    {
        return $this->db->execute('DELETE FROM teacher_subjects WHERE id = :id AND school_id = :school_id', ['id' => $id, 'school_id' => Tenant::id()]);
    }

    public function forStaff(int $staffId): array
    {
        return $this->db->select('SELECT ts.*, sub.name AS subject_name, c.name AS class_name, str.name AS stream_name FROM teacher_subjects ts INNER JOIN subjects sub ON sub.id = ts.subject_id LEFT JOIN classes c ON c.id = ts.class_id LEFT JOIN streams str ON str.id = ts.stream_id WHERE ts.staff_id = :staff_id ORDER BY sub.name ASC', ['staff_id' => $staffId]);
    }

    public function exists(int $staffId, int $subjectId, ?int $classId, ?int $streamId): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM teacher_subjects WHERE staff_id = :staff_id AND subject_id = :subject_id AND ';
        $params = ['staff_id' => $staffId, 'subject_id' => $subjectId];
        if ($classId === null) $sql .= 'class_id IS NULL';
        else { $sql .= 'class_id = :class_id AND '; $params['class_id'] = $classId; $sql .= $streamId === null ? 'stream_id IS NULL' : 'stream_id = :stream_id'; if ($streamId !== null) $params['stream_id'] = $streamId; }
        $sql .= ' AND school_id = :school_id'; $params['school_id'] = Tenant::id();
        $row = $this->db->selectOne($sql, $params);
        return (int) ($row['total'] ?? 0) > 0;
    }
}
