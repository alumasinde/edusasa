<?php

declare(strict_types=1);

namespace Modules\Teachers\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use Modules\Teachers\Services\TeacherPhase10Service;
use RuntimeException;

final class TeacherSubjectAutoAssignController extends BaseController
{
    public function __construct(private readonly TeacherPhase10Service $service) {}

    public function assignForClass(Request $request): Response
    {
        try {
            $classId = (int)$request->input('id', 0);
            // The Academic module owns class/subject assignment. This endpoint is intentionally
            // conservative until a curriculum-aware teacher workload allocator is introduced.
            if ($classId <= 0) throw new RuntimeException('Invalid class.');
            return $this->redirect('/academic/classes/'.$classId.'/subjects');
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 422);
        }
    }
}
