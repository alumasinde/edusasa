<?php

declare(strict_types=1);

namespace Modules\Teachers\Services;

use App\Core\AuditLog;
use App\Core\BaseService;
use Modules\Teachers\Repositories\TeacherRepository;
use RuntimeException;

final class TeacherCrudService extends BaseService
{
    public function __construct(private readonly TeacherRepository $teachers) {}
    public function list(array $filters,int $page=1,int $perPage=25):array{return $this->teachers->search($filters,$page,$perPage);}
    public function count(array $filters):int{return $this->teachers->countFiltered($filters);}
    public function find(int $id):array{$row=$this->teachers->findWithDepartment($id);if($row===null)throw new RuntimeException('Teacher not found.');return $row;}
    public function create(array $data):int{$id=(int)$this->teachers->create(['school_id'=>\App\Core\Tenant::id(),'employee_no'=>trim((string)($data['employee_no']??'')),'first_name'=>trim((string)$data['first_name']),'middle_name'=>trim((string)($data['middle_name']??''))?:null,'last_name'=>trim((string)$data['last_name']),'email'=>trim((string)($data['email']??''))?:null,'phone'=>trim((string)($data['phone']??''))?:null,'gender'=>$data['gender']??null,'department_id'=>($data['department_id']??'')!==''?(int)$data['department_id']:null,'employment_type'=>$data['employment_type']??null,'status'=>'active','joined_on'=>$data['joined_on']??null]);AuditLog::record('teacher.created','teachers',$id,null,$data);return $id;}
    public function update(int $id,array $data):void{$before=$this->find($id);$this->teachers->update($id,['first_name'=>trim((string)$data['first_name']),'middle_name'=>trim((string)($data['middle_name']??''))?:null,'last_name'=>trim((string)$data['last_name']),'email'=>trim((string)($data['email']??''))?:null,'phone'=>trim((string)($data['phone']??''))?:null,'gender'=>$data['gender']??null,'department_id'=>($data['department_id']??'')!==''?(int)$data['department_id']:null,'employment_type'=>$data['employment_type']??null,'status'=>$data['status']??$before['status'],'joined_on'=>$data['joined_on']??null,'left_on'=>$data['left_on']??null]);AuditLog::record('teacher.updated','teachers',$id,$before,$data);}
    public function setStatus(int $id,string $status):void{$before=$this->find($id);if(!in_array($status,['active','inactive','suspended','left'],true))throw new RuntimeException('Invalid teacher status.');$this->teachers->update($id,['status'=>$status]);AuditLog::record('teacher.status_changed','teachers',$id,['status'=>$before['status']],['status'=>$status]);}
    public function delete(int $id):void{$before=$this->find($id);$this->teachers->delete($id);AuditLog::record('teacher.deleted','teachers',$id,$before,null);}
}
