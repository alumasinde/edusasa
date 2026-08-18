<?php

declare(strict_types=1);
namespace Modules\Exams\Controllers;
use App\Core\BaseController;use App\Core\Request;use App\Core\Response;use Modules\Exams\Services\ReportCardRenderService;use RuntimeException;
final class ReportCardRenderController extends BaseController{
 public function __construct(private readonly ReportCardRenderService $service){}
 public function preview(Request $request):Response{try{return Response::html($this->service->html((int)$request->input('card_id',0)));}catch(RuntimeException $e){return Response::html($e->getMessage(),404);}}
 public function pdf(Request $request):Response{try{$html=$this->service->html((int)$request->input('card_id',0));if(!class_exists('Dompdf\\Dompdf'))return Response::html('PDF renderer is not installed. Run composer install after updating composer.json.',503);$pdf=new \Dompdf\Dompdf();$pdf->loadHtml($html);$pdf->setPaper('A4','portrait');$pdf->render();$name='report-card-'.$request->input('card_id').'.pdf';return Response::download($pdf->output(),$name,'application/pdf');}catch(RuntimeException $e){return Response::html($e->getMessage(),404);}}
}
