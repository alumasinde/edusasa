<?php

declare(strict_types=1);

namespace Modules\Students\Repositories;

use App\Core\BaseRepository;

class StudentDocumentRepository extends BaseRepository
{
    protected function table(): string { return 'student_documents'; }
    public function forStudent(int $studentId): array
    {
        return $this->db->select('SELECT sd.*,dt.name AS type_name FROM student_documents sd LEFT JOIN document_types dt ON dt.id=sd.document_type_id WHERE sd.student_id=:student_id AND sd.deleted_at IS NULL ORDER BY sd.created_at DESC',['student_id'=>$studentId]);
    }
}
