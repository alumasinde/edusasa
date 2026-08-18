<?php

declare(strict_types=1);

namespace Modules\Communication\Repositories;

use App\Core\Database;
use App\Core\Tenant;

final class CommunicationRepository
{
    public function __construct(private readonly Database $db) {}

    public function list(int $page=1,int $perPage=25): array {
        $offset=max(0,($page-1)*$perPage);
        return $this->db->select('SELECT c.*,CONCAT(u.first_name," ",u.last_name) sender_name FROM communications c LEFT JOIN users u ON u.id=c.sender_user_id WHERE c.school_id=:school_id ORDER BY c.created_at DESC LIMIT '.$perPage.' OFFSET '.$offset,['school_id'=>Tenant::id()]);
    }
    public function count(): int { $r=$this->db->selectOne('SELECT COUNT(*) total FROM communications WHERE school_id=:school_id',['school_id'=>Tenant::id()]);return(int)($r['total']??0); }
    public function find(int $id): ?array { return $this->db->selectOne('SELECT c.*,CONCAT(u.first_name," ",u.last_name) sender_name FROM communications c LEFT JOIN users u ON u.id=c.sender_user_id WHERE c.id=:id AND c.school_id=:school_id',['id'=>$id,'school_id'=>Tenant::id()]); }
    public function create(array $data): int { return(int)$this->db->insert('communications',array_merge(['school_id'=>Tenant::id()],$data)); }
    public function publish(int $id): void { $this->db->execute('UPDATE communications SET status="published",published_at=COALESCE(published_at,CURRENT_TIMESTAMP) WHERE id=:id AND school_id=:school_id',['id'=>$id,'school_id'=>Tenant::id()]); }
    public function archive(int $id): void { $this->db->execute('UPDATE communications SET status="archived" WHERE id=:id AND school_id=:school_id',['id'=>$id,'school_id'=>Tenant::id()]); }
    public function recipientsFor(array $message): array {
        $p=['school_id'=>Tenant::id()];
        if($message['audience_type']==='user') return $this->db->select('SELECT id user_id FROM users WHERE school_id=:school_id AND id=:user_id AND status="active" AND deleted_at IS NULL',['school_id'=>Tenant::id(),'user_id'=>(int)$message['audience_user_id']]);
        if($message['audience_type']==='role') return $this->db->select('SELECT DISTINCT u.id user_id FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE u.school_id=:school_id AND u.status="active" AND u.deleted_at IS NULL AND r.name=:role',['school_id'=>Tenant::id(),'role'=>$message['audience_role']]);
        if($message['audience_type']==='class'||$message['audience_type']==='stream') {
            $where='a.class_id=:class_id';$p['class_id']=(int)$message['audience_class_id'];
            if($message['audience_type']==='stream'){$where.=' AND a.stream_id=:stream_id';$p['stream_id']=(int)$message['audience_stream_id'];}
            $sql='SELECT DISTINCT u.id user_id FROM users u JOIN teachers t ON t.school_id=u.school_id AND t.email IS NOT NULL AND t.email=u.email JOIN teacher_class_assignments a ON a.teacher_id=t.id AND a.school_id=u.school_id WHERE u.school_id=:school_id AND u.status="active" AND u.deleted_at IS NULL AND a.status="active" AND '.$where;
            return $this->db->select($sql,$p);
        }
        return $this->db->select('SELECT id user_id FROM users WHERE school_id=:school_id AND status="active" AND deleted_at IS NULL',['school_id'=>Tenant::id()]);
    }
    public function addRecipients(int $communicationId,array $userIds): void { foreach($userIds as $userId)$this->db->execute('INSERT IGNORE INTO communication_recipients(communication_id,user_id,delivered_at) VALUES(:communication_id,:user_id,CURRENT_TIMESTAMP)',['communication_id'=>$communicationId,'user_id'=>(int)$userId]); }
    public function inbox(int $userId,int $page=1,int $perPage=25): array { $offset=max(0,($page-1)*$perPage);return $this->db->select('SELECT c.*,r.delivered_at,r.read_at FROM communication_recipients r JOIN communications c ON c.id=r.communication_id WHERE r.user_id=:user_id AND c.school_id=:school_id AND c.status="published" ORDER BY c.published_at DESC,c.id DESC LIMIT '.$perPage.' OFFSET '.$offset,['user_id'=>$userId,'school_id'=>Tenant::id()]); }
    public function unreadCount(int $userId): int { $r=$this->db->selectOne('SELECT COUNT(*) total FROM communication_recipients r JOIN communications c ON c.id=r.communication_id WHERE r.user_id=:user_id AND c.school_id=:school_id AND c.status="published" AND r.read_at IS NULL',['user_id'=>$userId,'school_id'=>Tenant::id()]);return(int)($r['total']??0); }
    public function markRead(int $communicationId,int $userId): void { $this->db->execute('UPDATE communication_recipients r JOIN communications c ON c.id=r.communication_id SET r.read_at=COALESCE(r.read_at,CURRENT_TIMESTAMP) WHERE r.communication_id=:communication_id AND r.user_id=:user_id AND c.school_id=:school_id',['communication_id'=>$communicationId,'user_id'=>$userId,'school_id'=>Tenant::id()]); }
}
