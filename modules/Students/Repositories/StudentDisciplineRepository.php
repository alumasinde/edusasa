<?php

declare(strict_types=1);

namespace Modules\Students\Repositories;

use App\Core\BaseRepository;

class StudentDisciplineRepository extends BaseRepository
{
    protected function table(): string { return 'student_discipline'; }
    public function forStudent(int $studentId): array { return $this->where(['student_id'=>$studentId],'incident_date DESC'); }
}
