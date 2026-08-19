<?php

declare(strict_types=1);

namespace Modules\Students\Repositories;

use App\Core\BaseRepository;
use App\Core\Tenant;
use App\Core\NotFoundException;

class StudentGuardianRepository extends BaseRepository
{
    protected function table(): string { return 'student_guardians'; }

    public function forStudent(int $studentId): array
    {
        return $this->db->select('SELECT sg.*,g.first_name,g.middle_name,g.last_name,g.email,g.phone,g.occupation,g.national_id,g.user_id AS guardian_user_id FROM student_guardians sg INNER JOIN guardians g ON g.id=sg.guardian_id WHERE sg.student_id=:student_id AND sg.deleted_at IS NULL ORDER BY sg.is_primary DESC,sg.created_at ASC',['student_id'=>$studentId]);
    }

    public function clearPrimaryFlag(int $studentId): void
    {
        $this->db->execute('UPDATE student_guardians SET is_primary=0 WHERE student_id=:student_id',['student_id'=>$studentId]);
    }

    public function existsForStudent(int $studentId,int $guardianId): bool
    {
        return $this->db->selectOne('SELECT id FROM student_guardians WHERE student_id=:student_id AND guardian_id=:guardian_id AND deleted_at IS NULL',['student_id'=>$studentId,'guardian_id'=>$guardianId]) !== null;
    }

    public function create(array $data): int
    {
        $columns=array_keys($data);$params=[];$placeholders=[];
        foreach($columns as $column){$placeholders[]=':'.$column;$params[$column]=$data[$column];}
        return(int)$this->db->insert('INSERT INTO student_guardians('.implode(',',$columns).') VALUES('.implode(',',$placeholders).')',$params);
    }

    public function findOrFail(int $id): array
    {
        $row=$this->db->selectOne('SELECT sg.* FROM student_guardians sg INNER JOIN students s ON s.id=sg.student_id WHERE sg.id=:id AND s.school_id=:school_id AND sg.deleted_at IS NULL',['id'=>$id,'school_id'=>Tenant::id()]);
        if($row===null)throw new NotFoundException('Student guardian relationship not found.');
        return$row;
    }

    public function delete(int $id): int
    {
        return$this->db->execute('UPDATE student_guardians sg INNER JOIN students s ON s.id=sg.student_id SET sg.deleted_at=CURRENT_TIMESTAMP WHERE sg.id=:id AND s.school_id=:school_id',['id'=>$id,'school_id'=>Tenant::id()]);
    }
}
