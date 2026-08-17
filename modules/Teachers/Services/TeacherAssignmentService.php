<?php

declare(strict_types=1);

namespace Modules\Teachers\Services;

use App\Core\AuditLog;
use App\Core\BaseService;
use App\Core\Tenant;
use Modules\Teachers\Repositories\TeacherSubjectRepository;

final class TeacherAssignmentService extends BaseService
{
    public function __construct(private readonly TeacherSubjectRepository $assignments) {}

    public function assign(int $staffId, int $subjectId, ?int $classId = null, ?int $streamId = null, int $periodsPerWeek = 0): int
    {
        if ($staffId < 1 || $subjectId < 1) {
            throw new \InvalidArgumentException('Teacher and subject are required.');
        }
        if ($classId === null && $streamId !== null) {
            throw new \InvalidArgumentException('A stream requires a class.');
        }
        if ($periodsPerWeek < 0 || $periodsPerWeek > 40) {
            throw new \InvalidArgumentException('Periods per week must be between 0 and 40.');
        }
        if ($this->assignments->exists($staffId, $subjectId, $classId, $streamId)) {
            throw new \DomainException('This teacher assignment already exists.');
        }

        $id = $this->assignments->create([
            'school_id' => Tenant::id(),
            'staff_id' => $staffId,
            'subject_id' => $subjectId,
            'class_id' => $classId,
            'stream_id' => $streamId,
            'periods_per_week' => $periodsPerWeek,
        ]);

        AuditLog::record('teacher.assignment_created', 'teacher_subject', $id, null, [
            'staff_id' => $staffId,
            'subject_id' => $subjectId,
            'class_id' => $classId,
            'stream_id' => $streamId,
            'periods_per_week' => $periodsPerWeek,
        ]);
        return (int) $id;
    }

    public function remove(int $id): void
    {
        $assignment = $this->assignments->find($id);
        if ($assignment === null) {
            throw new \RuntimeException('Teacher assignment not found.');
        }
        $this->assignments->delete($id);
        AuditLog::record('teacher.assignment_removed', 'teacher_subject', $id, $assignment, null);
    }
}
