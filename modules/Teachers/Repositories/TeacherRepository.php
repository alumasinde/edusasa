<?php

declare(strict_types=1);

namespace Modules\Teachers\Repositories;

use App\Core\BaseRepository;
use App\Core\Tenant;

final class TeacherRepository extends BaseRepository
{
    protected function table(): string { return 'teachers'; }
    public function search(array $filters,int $page=1,int $perPage=25):array{$where=['t.school_id=:school_id'];$p=['school_id'=>Tenant::id()];if(!empty($filters['q'])){$where[]='(t.first_name LIKE :q1 OR t.last_name LIKE :q2 OR t.employee_no LIKE :q3)';$q='%'.$filters['q'].'%';$p+=['q1'=>$q,'q2'=>$q,'q3'=>$q];}if(!empty($filters['department_id'])){$where[]='t.department_id=:department_id';$p['department_id']=(int)$filters['department_id'];}if(!empty($filters['status'])){$where[]='t.status=:status';$p['status']=$filters['status'];}$offset=max(0,($page-1)*$perPage);return$this->db->select('SELECT t.*, d.name department_name FROM teachers t LEFT JOIN departments d ON d.id=t.department_id WHERE '.implode(' AND ',$where).' ORDER BY t.first_name,t.last_name LIMIT '.$perPage.' OFFSET '.$offset,$p);}
    public function countFiltered(array $filters):int{$where=['t.school_id=:school_id'];$p=['school_id'=>Tenant::id()];if(!empty($filters['q'])){$where[]='(t.first_name LIKE :q1 OR t.last_name LIKE :q2 OR t.employee_no LIKE :q3)';$q='%'.$filters['q'].'%';$p+=['q1'=>$q,'q2'=>$q,'q3'=>$q];}if(!empty($filters['department_id'])){$where[]='t.department_id=:department_id';$p['department_id']=(int)$filters['department_id'];}if(!empty($filters['status'])){$where[]='t.status=:status';$p['status']=$filters['status'];}$r=$this->db->selectOne('SELECT COUNT(*) total FROM teachers t WHERE '.implode(' AND ',$where),$p);return(int)($r['total']??0);}
    public function findWithDepartment(int $id):?array{return$this->db->selectOne('SELECT t.*, d.name department_name FROM teachers t LEFT JOIN departments d ON d.id=t.department_id WHERE t.id=:id AND t.school_id=:school_id',['id'=>$id,'school_id'=>Tenant::id()]);}
    public function active():array{return$this->db->select("SELECT * FROM teachers WHERE school_id=:school_id AND status='active' ORDER BY first_name,last_name",['school_id'=>Tenant::id()]);}
    public function delete(int $id):int{$this->db->execute('DELETE FROM teacher_subjects WHERE teacher_id=:id AND school_id=:school_id',['id'=>$id,'school_id'=>Tenant::id()]);$this->db->execute('DELETE FROM teacher_class_assignments WHERE teacher_id=:id AND school_id=:school_id',['id'=>$id,'school_id'=>Tenant::id()]);return$this->db->execute('DELETE FROM teachers WHERE id=:id AND school_id=:school_id',['id'=>$id,'school_id'=>Tenant::id()]);}
}
