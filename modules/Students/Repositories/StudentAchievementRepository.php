<?php

declare(strict_types=1);

namespace Modules\Students\Repositories;

use App\Core\BaseRepository;

class StudentAchievementRepository extends BaseRepository
{
    protected function table(): string { return 'student_achievements'; }
    public function forStudent(int $studentId): array { return $this->where(['student_id'=>$studentId],'achieved_on DESC'); }
}
