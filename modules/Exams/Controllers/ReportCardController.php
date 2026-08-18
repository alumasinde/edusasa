<?php

declare(strict_types=1);
namespace Modules\Exams\Controllers;
use App\Core\BaseController;use App\Core\Request;use App\Core\Response;use App\Core\Session;use Modules\Exams\Services\ReportCardService;use RuntimeException;
final class ReportCardController{
 public function __construct(private readonly ReportCardService $service){}
 public function index(Request $request):Response{try{return Response::view('exams.report-cards',['data'=>$this->service->pageData((int)$request->input('id',0))]);}catch(RuntimeException $e){return Response::html($e->getMessage(),404);}}
 public function review(Request $request):Response{try{return Response::view('exams.report-card-review',['data'=>$this->service->reviewData((int)$request->input('card_id',0))]);}catch(RuntimeException $e){return Response::html($e->getMessage(),404);}}
 public function saveRemarks(Request $request):Response{$card=(int)$request->input('card_id',0);try{$this->service->updateRemarks($card,(string)$request->input('teacher_remark',''),(string)$request->input('principal_remark',''),(int)(Session::get('user_id')??0));Session::flash('success','Remarks saved.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/exams/report-cards/'.$card.'/review');}
 public function approve(Request $request):Response{$card=(int)$request->input('card_id',0);try{$this->service->approve($card,(int)(Session::get('user_id')??0));Session::flash('success','Report card approved.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/exams/report-cards/'.$card.'/review');}
 public function generate(Request $request):Response{$id=(int)$request->input('id',0);try{$this->service->generate($id);Session::flash('success','Report cards generated.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/exams/'.$id.'/report-cards');}
 public function publish(Request $request):Response{$examId=(int)$request->input('id',0);try{$this->service->publish((int)$request->input('card_id',0));Session::flash('success','Report card published.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/exams/'.$examId.'/report-cards');}
}
