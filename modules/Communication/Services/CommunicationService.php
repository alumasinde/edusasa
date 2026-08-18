<?php

declare(strict_types=1);

namespace Modules\Communication\Services;

use App\Core\AuditLog;
use App\Core\BaseService;
use App\Core\Tenant;
use App\Core\ValidationException;
use Modules\Communication\Repositories\CommunicationRepository;

final class CommunicationService extends BaseService
{
    public function __construct(private readonly CommunicationRepository $communications) {}

    public function create(array $data): int {
        $title=trim((string)($data['title']??''));$body=trim((string)($data['body']??''));$type=(string)($data['type']??'announcement');$audience=(string)($data['audience_type']??'all');
        if($title===''||mb_strlen($title)>190)throw new ValidationException(['title'=>['A title of 1–190 characters is required.']]);
        if($body==='')throw new ValidationException(['body'=>['Message body is required.']]);
        if(!in_array($type,['announcement','message','notice'],true))throw new ValidationException(['type'=>['Invalid communication type.']]);
        if(!in_array($audience,['all','role','class','stream','user'],true))throw new ValidationException(['audience_type'=>['Invalid audience.']]);
        if($audience==='role'&&trim((string)($data['audience_role']??''))==='')throw new ValidationException(['audience_role'=>['Select a role.']]);
        if(in_array($audience,['class','stream'],true)&&(int)($data['audience_class_id']??0)<1)throw new ValidationException(['audience_class_id'=>['Select a class.']]);
        if($audience==='stream'&&(int)($data['audience_stream_id']??0)<1)throw new ValidationException(['audience_stream_id'=>['Select a stream.']]);
        if($audience==='user'&&(int)($data['audience_user_id']??0)<1)throw new ValidationException(['audience_user_id'=>['Select a recipient.']]);
        $id=$this->communications->create(['sender_user_id'=>$data['sender_user_id']??null,'title'=>$title,'body'=>$body,'type'=>$type,'audience_type'=>$audience,'audience_role'=>$audience==='role'?trim((string)$data['audience_role']):null,'audience_class_id'=>in_array($audience,['class','stream'],true)?(int)$data['audience_class_id']:null,'audience_stream_id'=>$audience==='stream'?(int)$data['audience_stream_id']:null,'audience_user_id'=>$audience==='user'?(int)$data['audience_user_id']:null,'status'=>'draft']);
        AuditLog::record('communication.created','communications',$id,null,['audience_type'=>$audience,'type'=>$type]);return $id;
    }
    public function publish(int $id): int { $message=$this->communications->find($id);if($message===null)throw new ValidationException(['communication'=>['Communication not found.']]);if($message['status']==='published')return 0;$recipients=$this->communications->recipientsFor($message);$this->db()->transaction(function()use($id,$recipients):void{$this->communications->addRecipients($id,array_column($recipients,'user_id'));$this->communications->publish($id);});AuditLog::record('communication.published','communications',$id,null,['recipient_count'=>count($recipients)]);return count($recipients);}
    public function markRead(int $id,int $userId): void { $this->communications->markRead($id,$userId); }
}
