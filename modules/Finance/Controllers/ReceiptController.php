<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use Modules\Finance\Services\ReceiptService;
use RuntimeException;

final class ReceiptController extends BaseController
{
    public function __construct(private readonly ReceiptService $service) {}

    public function show(Request $request): Response
    {
        try {
            $receipt=$this->service->get((int)$request->input('id'));
            if(!$receipt) throw new RuntimeException('Receipt not found.');
            return $this->view('finance.receipt',['receipt'=>$receipt]);
        } catch(RuntimeException $e) {
            return Response::html('<h1>Receipt unavailable</h1><p>'.htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8').'</p>',404);
        }
    }
}
