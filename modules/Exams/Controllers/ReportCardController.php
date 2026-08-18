<?php

declare(strict_types=1);
namespace Modules\Exams\Controllers;
use App\Core\BaseController;use App\Core\Request;use App\Core\Response;use App\Core\Session;use Modules\Exams\Services\ReportCardService;use RuntimeException;
final class ReportCardController extends BaseController{
 public function __construct(private readonly ReportCardService $service){}
 public function index(Request $request):Response{try{return $this->view('exams.report-cards',['data'=>$this->service->pageData((int)$request->input('id',0))]);}catch(RuntimeException $e){return Response::html($e->getMessage(),404);}}
 public function generate(Request $request):Response{$id=(int)$request->input('id',0);try{$this->service->generate($id);Session::flash('success','Report cards generated.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/exams/'.$id.'/report-cards');}
 public function publish(Request $request):Response{$examId=(int)$request->input('id',0);try{$this->service->publish((int)$request->input('card_id',0));Session::flash('success','Report card published.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/exams/'.$examId.'/report-cards');}
}
