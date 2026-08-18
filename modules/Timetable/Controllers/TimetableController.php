<?php

declare(strict_types=1);

namespace Modules\Timetable\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use Modules\Timetable\Repositories\TimetableRepository;
use Modules\Timetable\Services\TimetableService;

final class TimetableController extends BaseController
{
    public function __construct(private readonly TimetableRepository $repo,private readonly TimetableService $service) {}
    public function index(Request $request): Response { $termId=(int)$request->input('term_id',0);$tt=$this->repo->latest($termId>0?$termId:null);return $this->view('timetable.index',['timetable'=>$tt,'years'=>$this->repo->academicYears(),'terms'=>$this->repo->terms(),'classes'=>$this->repo->classes(),'periods'=>$this->repo->periods(),'entries'=>$tt?$this->repo->grid((int)$tt['id'],null,null):[],'selectedTerm'=>$termId]); }
    public function create(Request $request): Response { return $this->view('timetable.create',['years'=>$this->repo->academicYears(),'terms'=>$this->repo->terms(),'errors'=>[]]); }
    public function store(Request $request): Response { try{$id=$this->service->create((int)$request->input('academic_year_id',0),(int)$request->input('term_id',0),(string)$request->input('name',''));return $this->redirect('/timetable/'.$id,'Timetable created.');}catch(ValidationException $e){return $this->view('timetable.create',['years'=>$this->repo->academicYears(),'terms'=>$this->repo->terms(),'errors'=>$e->errors()],422);} }
    public function show(Request $request,array $params): Response { $id=(int)$params['id'];$tt=$this->repo->findTimetable($id);if(!$tt)return $this->notFound();$classId=(int)$request->input('class_id',0);$streamId=$request->input('stream_id','')!==''?(int)$request->input('stream_id'):null;return $this->view('timetable.show',['timetable'=>$tt,'entries'=>$this->repo->grid($id,$classId>0?$classId:null,$streamId),'classes'=>$this->repo->classes(),'streams'=>$classId>0?$this->repo->streams($classId):[],'teachers'=>$this->repo->teachers(),'subjects'=>$this->repo->subjects(),'classId'=>$classId,'streamId'=>$streamId,'periods'=>$this->repo->periods(),'days'=>[1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday']]); }
    public function generate(Request $request,array $params): Response { try{$result=$this->service->generate((int)$params['id']);Session::flash('success','Timetable generated: '.$result['placed'].' lessons placed'.($result['failed']?' and '.$this->failedCount($result['failed']).' workload items could not be placed.':''));return $this->redirect('/timetable/'.$params['id']);}catch(ValidationException $e){Session::flash('error',implode(' ',array_merge(...array_values($e->errors()))));return $this->redirect('/timetable/'.$params['id']);} }
    public function publish(Request $request,array $params): Response { $this->service->publish((int)$params['id']);Session::flash('success','Timetable published.');return $this->redirect('/timetable/'.$params['id']); }
    public function clear(Request $request,array $params): Response { $this->repo->clearEntries((int)$params['id']);Session::flash('success','Timetable entries cleared.');return $this->redirect('/timetable/'.$params['id']); }
    public function add(Request $request,array $params): Response { try{$stream=$request->input('stream_id','');$this->service->addEntry((int)$params['id'],(int)$request->input('class_id',0),$stream!==''?(int)$stream:null,(int)$request->input('teacher_id',0),(int)$request->input('subject_id',0),(int)$request->input('day_of_week',0),(int)$request->input('period_id',0),(string)$request->input('entry_type','lesson'));return $this->redirect('/timetable/'.$params['id'],'Lesson added.');}catch(ValidationException $e){Session::flash('error',implode(' ',array_merge(...array_values($e->errors()))));return $this->redirect('/timetable/'.$params['id']);} }
    private function failedCount(array $failed): int { return count($failed); }
}
