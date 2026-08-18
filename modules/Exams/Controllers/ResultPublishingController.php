<?php

declare(strict_types=1);

namespace Modules\Exams\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Modules\Exams\Services\ResultPublishingService;
use RuntimeException;

final class ResultPublishingController extends BaseController
{
    public function __construct(private readonly ResultPublishingService $service) {}

    public function index(Request $request): Response
    {
        try{return $this->view('exams.results-approval',['data'=>$this->service->pageData((int)$request->input('id',0))]);}
        catch(RuntimeException $e){return Response::html($e->getMessage(),404);}
    }
    public function approve(Request $request): Response
    {
        $id=(int)$request->input('id',0);try{$this->service->approve($id,(int)(Session::get('user_id')??0));Session::flash('success','Results approved.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/exams/'.$id.'/results');
    }
    public function publish(Request $request): Response
    {
        $id=(int)$request->input('id',0);try{$this->service->publish($id,(int)(Session::get('user_id')??0));Session::flash('success','Results published.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/exams/'.$id.'/results');
    }
    public function returnForCorrection(Request $request): Response
    {
        $id=(int)$request->input('id',0);try{$this->service->returnForCorrection($id,(int)(Session::get('user_id')??0),(string)$request->input('reason',''));Session::flash('success','Results returned for correction.');}catch(RuntimeException $e){Session::flash('error',$e->getMessage());}return $this->redirect('/exams/'.$id.'/results');
    }
}
