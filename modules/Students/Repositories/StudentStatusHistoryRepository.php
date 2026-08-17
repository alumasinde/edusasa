<?php

declare(strict_types=1);

namespace Modules\Students\Repositories;

use App\Core\BaseRepository;

class StudentStatusHistoryRepository extends BaseRepository
{
    protected function table(): string { return 'student_status_history'; }
    public function forStudent(int $studentId): array { return $this->where(['student_id'=>$studentId],'created_at DESC'); }
}
