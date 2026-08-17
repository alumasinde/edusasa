<?php

declare(strict_types=1);

namespace Modules\Students\Repositories;

use App\Core\BaseRepository;

class StudentGuardianRepository extends BaseRepository
{
    protected function table(): string { return 'student_guardians'; }

    public function forStudent(int $studentId): array
    {
        return $this->db->select(
            'SELECT sg.*,g.first_name,g.middle_name,g.last_name,g.email,g.phone,g.occupation,g.national_id,g.user_id AS guardian_user_id FROM student_guardians sg INNER JOIN guardians g ON g.id=sg.guardian_id WHERE sg.student_id=:student_id AND sg.deleted_at IS NULL ORDER BY sg.is_primary DESC,sg.created_at ASC',
            ['student_id'=>$studentId]
        );
    }

    public function clearPrimaryFlag(int $studentId): void
    {
        $this->db->execute('UPDATE student_guardians SET is_primary=0 WHERE student_id=:student_id',['student_id'=>$studentId]);
    }

    public function existsForStudent(int $studentId,int $guardianId): bool
    {
        return $this->db->selectOne('SELECT id FROM student_guardians WHERE student_id=:student_id AND guardian_id=:guardian_id AND deleted_at IS NULL',['student_id'=>$studentId,'guardian_id'=>$guardianId]) !== null;
    }
}
