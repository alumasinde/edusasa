<?php

declare(strict_types=1);
namespace Modules\Communication\Controllers;
use App\Core\Auth;
use App\Core\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Tenant;
use App\Core\ValidationException;
use Modules\Communication\Repositories\CommunicationRepository;
use Modules\Communication\Services\CommunicationService;
final class CommunicationController extends BaseController
{
 public function __construct(private readonly CommunicationRepository $repo,private readonly CommunicationService $service,private readonly Auth $auth,private readonly Database $db) {}
 public function index(Request $request): Response { $page=max(1,(int)$request->input('page',1));$total=$this->repo->count();return $this->view('communication.index',['messages'=>$this->repo->list($page),'page'=>$page,'totalPages'=>(int)max(1,ceil($total/25))]); }
 private function options():array {return ['roles'=>$this->db->select('SELECT id,name FROM roles ORDER BY name'),'classes'=>$this->db->select('SELECT id,name FROM classes WHERE school_id=:school_id AND status="active" ORDER BY name',['school_id'=>Tenant::id()]),'users'=>$this->db->select('SELECT id,email FROM users WHERE school_id=:school_id AND status="active" AND deleted_at IS NULL ORDER BY email',['school_id'=>Tenant::id()])];}
 public function create(Request $request): Response {return $this->view('communication.form',array_merge($this->options(),['errors'=>[]]));}
 public function store(Request $request): Response {try{$data=$this->validate($request,['title'=>'required|max:190','body'=>'required','type'=>'required|in:announcement,message,notice','audience_type'=>'required|in:all,role,class,stream,user']);$data['audience_role']=$request->input('audience_role','');$data['audience_class_id']=(int)$request->input('audience_class_id',0);$data['audience_stream_id']=(int)$request->input('audience_stream_id',0);$data['audience_user_id']=(int)$request->input('audience_user_id',0);$data['sender_user_id']=$this->auth->id();$id=$this->service->create($data);return $this->redirect('/communication/'.$id,'Communication saved.');}catch(ValidationException $e){return $this->view('communication.form',array_merge($this->options(),['errors'=>$e->errors()]),422);}}
 public function show(Request $request,array $params): Response {$message=$this->repo->find((int)$params['id']);if($message===null)return $this->notFound();return $this->view('communication.show',['message'=>$message]);}
 public function publish(Request $request,array $params): Response {try{$count=$this->service->publish((int)$params['id']);return $this->redirect('/communication/'.(int)$params['id'],'Message published to '.$count.' recipient(s).');}catch(ValidationException $e){return $this->redirect('/communication/'.(int)$params['id'],'Unable to publish message.');}}
 public function archive(Request $request,array $params): Response {$this->repo->archive((int)$params['id']);return $this->redirect('/communication','Communication archived.');}
 public function inbox(Request $request): Response {$userId=$this->auth->id();if($userId===null)return $this->notFound();return $this->view('communication.inbox',['messages'=>$this->repo->inbox($userId,(int)$request->input('page',1)),'unread'=>$this->repo->unreadCount($userId)]);}
 public function read(Request $request,array $params): Response {$userId=$this->auth->id();if($userId!==null)$this->service->markRead((int)$params['id'],$userId);return $this->redirect('/communication/inbox');}
}
