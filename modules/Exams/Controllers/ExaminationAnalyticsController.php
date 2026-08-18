<?php
declare(strict_types=1);

namespace Modules\Exams\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use Modules\Exams\Services\ExaminationAnalyticsService;
use RuntimeException;

final class ExaminationAnalyticsController extends BaseController
{
    public function __construct(
        private readonly ExaminationAnalyticsService $service
    ) {}

    public function index(Request $request): Response
    {
        try {
            return $this->view(
                'exams.analytics',
                ['data' => $this->service->dashboard((int)$request->input('id', 0))]
            );
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }

    public function csv(Request $request): Response
    {
        try {
            $id = (int)$request->input('id', 0);
            return Response::download(
                $this->service->csv($id),
                'examination-analytics-'.$id.'.csv',
                'text/csv'
            );
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }
}
