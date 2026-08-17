<?php

declare(strict_types=1);

namespace Modules\Students\Repositories;

use App\Core\BaseRepository;
use App\Core\Tenant;

class GuardianRepository extends BaseRepository
{
    protected function table(): string { return 'guardians'; }

    public function findByEmailOrPhone(string $email, string $phone): ?array
    {
        if ($email === '' && $phone === '') return null;
        $conditions = [];
        $params = ['school_id' => Tenant::id()];
        if ($email !== '') { $conditions[] = 'email = :email'; $params['email'] = $email; }
        if ($phone !== '') { $conditions[] = 'phone = :phone'; $params['phone'] = $phone; }
        return $this->db->selectOne(
            'SELECT * FROM guardians WHERE school_id = :school_id AND deleted_at IS NULL AND (' . implode(' OR ', $conditions) . ') LIMIT 1',
            $params
        );
    }

    public function search(string $term): array
    {
        $like = '%' . $term . '%';
        return $this->db->select(
            'SELECT * FROM guardians WHERE school_id = :school_id AND deleted_at IS NULL AND (first_name LIKE :t1 OR last_name LIKE :t2 OR email LIKE :t3 OR phone LIKE :t4 OR national_id LIKE :t5) ORDER BY first_name ASC LIMIT 20',
            ['school_id'=>Tenant::id(),'t1'=>$like,'t2'=>$like,'t3'=>$like,'t4'=>$like,'t5'=>$like]
        );
    }

    public function linkedStudents(int $guardianId): array
    {
        return $this->db->select(
            'SELECT s.id,s.admission_no,s.first_name,s.last_name,sg.relationship,sg.is_primary,c.name AS class_name FROM student_guardians sg INNER JOIN students s ON s.id=sg.student_id LEFT JOIN classes c ON c.id=s.current_class_id WHERE sg.guardian_id=:guardian_id AND sg.deleted_at IS NULL AND s.deleted_at IS NULL ORDER BY s.first_name ASC',
            ['guardian_id'=>$guardianId]
        );
    }

    public function fullName(array $guardian): string
    {
        $middle = ($guardian['middle_name'] ?? '') !== '' ? $guardian['middle_name'] . ' ' : '';
        return trim($guardian['first_name'].' '.$middle.$guardian['last_name']);
    }
}
