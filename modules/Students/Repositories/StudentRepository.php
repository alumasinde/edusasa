<?php

declare(strict_types=1);

namespace Modules\Students\Repositories;

use App\Core\BaseRepository;
use App\Core\Tenant;

class StudentRepository extends BaseRepository
{
    protected function table(): string { return 'students'; }
    public function fullName(array $student): string { return trim($student['first_name'].' '.($student['middle_name']?$student['middle_name'].' ':'').$student['last_name']); }
    public function search(array $filters,int $page=1,int $perPage=25): array
    {
        [$where,$params]=$this->buildFilterClause($filters);$offset=max(0,($page-1)*$perPage);
        return $this->db->select("SELECT s.*, c.name AS class_name, st.name AS stream_name FROM students s LEFT JOIN classes c ON c.id=s.current_class_id LEFT JOIN streams st ON st.id=s.current_stream_id {$where} ORDER BY s.first_name ASC,s.last_name ASC LIMIT {$perPage} OFFSET {$offset}",$params);
    }
    public function countFiltered(array $filters): int { [$where,$params]=$this->buildFilterClause($filters);$row=$this->db->selectOne("SELECT COUNT(*) AS total FROM students s {$where}",$params);return (int)($row['total']??0); }
    private function buildFilterClause(array $filters): array
    {
        $conditions=['s.school_id = :school_id','s.deleted_at IS NULL'];$params=['school_id'=>Tenant::id()];
        if(!empty($filters['q'])){$conditions[]='(s.first_name LIKE :q1 OR s.last_name LIKE :q2 OR s.admission_no LIKE :q3)';$like='%'.$filters['q'].'%';$params['q1']=$like;$params['q2']=$like;$params['q3']=$like;}
        if(!empty($filters['class_id'])){$conditions[]='s.current_class_id = :class_id';$params['class_id']=(int)$filters['class_id'];}
        if(!empty($filters['stream_id'])){$conditions[]='s.current_stream_id = :stream_id';$params['stream_id']=(int)$filters['stream_id'];}
        if(!empty($filters['status'])){$conditions[]='s.status = :status';$params['status']=$filters['status'];}
        return ['WHERE '.implode(' AND ',$conditions),$params];
    }
    public function findWithClass(int $id): ?array { return $this->db->selectOne('SELECT s.*, c.name AS class_name, st.name AS stream_name FROM students s LEFT JOIN classes c ON c.id=s.current_class_id LEFT JOIN streams st ON st.id=s.current_stream_id WHERE s.id=:id AND s.school_id=:school_id AND s.deleted_at IS NULL',['id'=>$id,'school_id'=>Tenant::id()]); }
    public function nextAdmissionNumber(): string
    {
        $tenant=Tenant::current();$prefix=$tenant?->admissionNoPrefix??'ADM';$padding=$tenant?->admissionNoPadding??4;
        $row=$this->db->selectOne('SELECT admission_no FROM students WHERE school_id=:school_id AND admission_no LIKE :prefix ORDER BY id DESC LIMIT 1',['school_id'=>Tenant::id(),'prefix'=>$prefix.'%']);$lastNumber=0;
        if($row!==null&&preg_match('/(\d+)$/',(string)$row['admission_no'],$m))$lastNumber=(int)$m[1];
        return $prefix.str_pad((string)($lastNumber+1),$padding,'0',STR_PAD_LEFT);
    }
}