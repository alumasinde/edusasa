<?php

declare(strict_types=1);

namespace Modules\Exams\Services;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class ExaminationSetupService
{
    public function __construct(private readonly Database $db) {}

    public function exams(): array
    {
        return $this->db->select('SELECT e.*, ay.name academic_year_name, t.name term_name,
            (SELECT COUNT(*) FROM examination_classes ec WHERE ec.examination_id=e.id) class_count
            FROM examinations e JOIN academic_years ay ON ay.id=e.academic_year_id
            JOIN terms t ON t.id=e.term_id WHERE e.school_id=:school_id
            ORDER BY e.starts_on DESC,e.id DESC',['school_id'=>Tenant::id()]);
    }

    public function formData(): array
    {
        $schoolId=Tenant::id();
        return [
            'years'=>$this->db->select('SELECT id,name,starts_on,ends_on FROM academic_years WHERE school_id=:school_id AND status<>"closed" ORDER BY starts_on DESC',['school_id'=>$schoolId]),
            'terms'=>$this->db->select('SELECT id,academic_year_id,name,starts_on,ends_on FROM terms WHERE school_id=:school_id ORDER BY starts_on DESC',['school_id'=>$schoolId]),
            'classes'=>$this->db->select('SELECT id,name,code FROM classes WHERE school_id=:school_id AND status="active" ORDER BY name',['school_id'=>$schoolId]),
        ];
    }

    public function create(array $data, array $classIds, int $userId): int
    {
        $schoolId=Tenant::id();
        $name=trim((string)($data['name']??'')); $code=strtoupper(trim((string)($data['code']??'')));
        $type=trim((string)($data['exam_type']??'')); $yearId=(int)($data['academic_year_id']??0); $termId=(int)($data['term_id']??0);
        $start=(string)($data['starts_on']??''); $end=(string)($data['ends_on']??'');
        if($name===''||$code===''||$type===''||$yearId<1||$termId<1||$start===''||$end==='') throw new RuntimeException('Complete all required examination fields.');
        if($end<$start) throw new RuntimeException('End date cannot be before start date.');
        $year=$this->db->selectOne('SELECT id,starts_on,ends_on FROM academic_years WHERE id=:id AND school_id=:school_id',['id'=>$yearId,'school_id'=>$schoolId]);
        $term=$this->db->selectOne('SELECT id,academic_year_id,starts_on,ends_on FROM terms WHERE id=:id AND school_id=:school_id',['id'=>$termId,'school_id'=>$schoolId]);
        if(!$year||!$term||((int)$term['academic_year_id']!==$yearId)) throw new RuntimeException('Selected academic year and term do not belong together.');
        if($start<$term['starts_on']||$end>$term['ends_on']) throw new RuntimeException('Examination dates must fall within the selected term.');
        $valid=[]; foreach($classIds as $id){$id=(int)$id;if($id>0)$valid[$id]=$id;}
        if(!$valid) throw new RuntimeException('Select at least one class.');
        $classes=$this->db->select('SELECT id FROM classes WHERE school_id=:school_id AND status="active" AND id IN ('.implode(',',array_keys($valid)).')',['school_id'=>$schoolId]);
        if(count($classes)!==count($valid)) throw new RuntimeException('One or more selected classes are invalid.');
        return (int)$this->db->transaction(function()use($schoolId,$yearId,$termId,$name,$code,$type,$start,$end,$data,$userId,$valid){
            $id=(int)$this->db->insert('INSERT INTO examinations(school_id,academic_year_id,term_id,name,code,exam_type,starts_on,ends_on,status,instructions,created_by) VALUES(:school_id,:year_id,:term_id,:name,:code,:type,:start,:end,"draft",:instructions,:user_id)',[
                'school_id'=>$schoolId,'year_id'=>$yearId,'term_id'=>$termId,'name'=>$name,'code'=>$code,'type'=>$type,'start'=>$start,'end'=>$end,'instructions'=>trim((string)($data['instructions']??''))?:null,'user_id'=>$userId]);
            foreach($valid as $classId)$this->db->execute('INSERT INTO examination_classes(examination_id,class_id,school_id) VALUES(:exam_id,:class_id,:school_id)',['exam_id'=>$id,'class_id'=>$classId,'school_id'=>$schoolId]);
            return $id;
        });
    }

    public function changeStatus(int $id,string $status): void
    {
        $allowed=['draft','scheduled','open','closed','published','cancelled']; if(!in_array($status,$allowed,true))throw new RuntimeException('Invalid examination status.');
        $exam=$this->db->selectOne('SELECT id,status FROM examinations WHERE id=:id AND school_id=:school_id',['id'=>$id,'school_id'=>Tenant::id()]); if(!$exam)throw new RuntimeException('Examination not found.');
        $current=$exam['status']; $transitions=['draft'=>['scheduled','cancelled'],'scheduled'=>['open','draft','cancelled'],'open'=>['closed'],'closed'=>['published'],'published'=>[],'cancelled'=>[]];
        if(!in_array($status,$transitions[$current]??[],true))throw new RuntimeException('Invalid status transition from '.$current.' to '.$status.'.');
        $this->db->execute('UPDATE examinations SET status=:status WHERE id=:id AND school_id=:school_id',['status'=>$status,'id'=>$id,'school_id'=>Tenant::id()]);
    }
}
