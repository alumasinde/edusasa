<?php

declare(strict_types=1);

namespace Modules\Students\Repositories;

use App\Core\BaseRepository;

class StudentMedicalRepository extends BaseRepository
{
    protected function table(): string { return 'student_medical'; }
    public function forStudent(int $studentId): ?array { return $this->whereFirst(['student_id'=>$studentId]); }
}
