<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Tenant;
use Modules\Finance\Services\FinanceReportService;
use Throwable;

final class FinanceReportController extends BaseController
{
    public function __construct(private readonly FinanceReportService $service) {}

    public function index(Request $request): Response
    {
        try {
            $filters = $this->filters($request);
            return $this->view('finance.reports', $this->service->dashboard(Tenant::id(), $filters));
        } catch (Throwable $e) {
            return Response::html('Unable to load finance reports.', 500);
        }
    }

    public function export(Request $request): Response
    {
        try {
            $filters = $this->filters($request);
            $report = $this->service->export(Tenant::id(), $filters);
            return Response::json(['success' => true, 'data' => $report]);
        } catch (Throwable $e) {
            return Response::json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function filters(Request $request): array
    {
        $from = (string)$request->input('from', date('Y-m-01'));
        $to = (string)$request->input('to', date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
        if ($from > $to) [$from, $to] = [$to, $from];
        return ['from' => $from, 'to' => $to, 'class_id' => max(0, (int)$request->input('class_id', 0)), 'method' => trim((string)$request->input('method', ''))];
    }
}
