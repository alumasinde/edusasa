<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Tenant;
use Modules\Finance\Services\FinanceIntegrityService;
use Throwable;

final class FinanceIntegrityController extends BaseController
{
    public function __construct(private readonly FinanceIntegrityService $service) {}

    public function index(Request $request): Response
    {
        try { return $this->view('finance.integrity', ['summary'=>$this->service->summary(Tenant::id()), 'issues'=>$this->service->scan(Tenant::id())]); }
        catch (Throwable $e) { return Response::html('Unable to run finance integrity checks.',500); }
    }

    public function scan(Request $request): Response
    {
        try { return Response::json(['success'=>true,'data'=>$this->service->scan(Tenant::id())]); }
        catch (Throwable $e) { return Response::json(['success'=>false,'message'=>'Integrity scan failed.'],500); }
    }
}
