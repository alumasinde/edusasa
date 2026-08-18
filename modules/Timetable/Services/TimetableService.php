<?php

declare(strict_types=1);

namespace Modules\Timetable\Services;

use App\Core\BaseService;
use App\Core\Tenant;
use App\Core\ValidationException;
use Modules\Timetable\Repositories\TimetableRepository;

final class TimetableService extends BaseService
{
    public function __construct(private readonly TimetableRepository $timetables) {}

    public function create(int $academicYearId,int $termId,string $name): int
    {
        if($academicYearId<1||$termId<1||trim($name)==='') throw new ValidationException(['timetable'=>['Academic year, term and timetable name are required.']]);
        return $this->timetables->createTimetable($academicYearId,$termId,trim($name));
    }

    public function addEntry(int $timetableId,int $classId,?int $streamId,int $teacherId,int $subjectId,int $day,int $periodId,string $type='lesson'): int
    {
        if($timetableId<1||$classId<1||$teacherId<1||$subjectId<1||$periodId<1||$day<1||$day>5) throw new ValidationException(['entry'=>['Invalid timetable entry.']]);
        $conflicts=$this->timetables->conflicts($timetableId,$classId,$streamId,$teacherId,$day,$periodId);
        if($conflicts) throw new ValidationException(['slot'=>['This slot has a class or teacher conflict.']]);
        return $this->timetables->insertEntry(['timetable_id'=>$timetableId,'class_id'=>$classId,'stream_id'=>$streamId,'teacher_id'=>$teacherId,'subject_id'=>$subjectId,'period_id'=>$periodId,'day_of_week'=>$day,'entry_type'=>in_array($type,['lesson','double','activity'],true)?$type:'lesson']);
    }

    /** Greedy constraint-aware generator. It never overwrites an existing entry. */
    public function generate(int $timetableId): array
    {
        $periods=array_values(array_filter($this->timetables->activePeriods(),fn($p)=>(int)$p['is_break']===0));
        if(!$periods) throw new ValidationException(['periods'=>['Configure at least one teaching period first.']]);
        $assignments=$this->timetables->assignments();
        if(!$assignments) throw new ValidationException(['assignments'=>['No class-specific teacher assignments are available. Assign teachers to classes first.']]);
        $this->timetables->clearEntries($timetableId);
        $placed=0;$failed=[];$teacherLoad=[];$classLoad=[];
        foreach($assignments as $a){
            $needed=max(1,(int)$a['periods_per_week']);
            $isDouble=(int)$a['is_double']===1;
            $remaining=$needed;$guard=0;
            while($remaining>0&&$guard++<500){
                $best=null;$bestScore=PHP_INT_MAX;
                foreach(range(1,5) as $day){
                    foreach($periods as $index=>$p){
                        if($isDouble && $remaining>1 && !isset($periods[$index+1])) continue;
                        $pid=(int)$p['id'];
                        if($isDouble && $remaining>1 && (int)$periods[$index+1]['period_no']!==(int)$p['period_no']+1) continue;
                        $conf=$this->timetables->conflicts($timetableId,(int)$a['class_id'],$a['stream_id']!==null?(int)$a['stream_id']:null,(int)$a['teacher_id'],(int)$day,$pid);
                        if($conf) continue;
                        $score=($teacherLoad[(int)$a['teacher_id']]??0)*10+($classLoad[(string)$a['class_id'].':'.($a['stream_id']??'')]??0);
                        $score+=($day-1)*2+(int)$p['period_no'];
                        if($score<$bestScore)$best=[$day,$index,$p,$score];$bestScore=$score;
                    }
                }
                if($best===null) break;
                [$day,$index,$p]=$best;
                $this->timetables->insertEntry(['timetable_id'=>$timetableId,'class_id'=>(int)$a['class_id'],'stream_id'=>$a['stream_id']!==null?(int)$a['stream_id']:null,'teacher_id'=>(int)$a['teacher_id'],'subject_id'=>(int)$a['subject_id'],'period_id'=>(int)$p['id'],'day_of_week'=>$day,'entry_type'=>$isDouble?'double':'lesson']);
                $placed++;$remaining--; $teacherLoad[(int)$a['teacher_id']]=($teacherLoad[(int)$a['teacher_id']]??0)+1;$key=(string)$a['class_id'].':'.($a['stream_id']??'');$classLoad[$key]=($classLoad[$key]??0)+1;
                if($isDouble&&$remaining>0){$next=$periods[$index+1]??null;if($next!==null){$conf=$this->timetables->conflicts($timetableId,(int)$a['class_id'],$a['stream_id']!==null?(int)$a['stream_id']:null,(int)$a['teacher_id'],(int)$day,(int)$next['id']);if(!$conf){$this->timetables->insertEntry(['timetable_id'=>$timetableId,'class_id'=>(int)$a['class_id'],'stream_id'=>$a['stream_id']!==null?(int)$a['stream_id']:null,'teacher_id'=>(int)$a['teacher_id'],'subject_id'=>(int)$a['subject_id'],'period_id'=>(int)$next['id'],'day_of_week'=>$day,'entry_type'=>'double']);$placed++;$remaining--;}}}
            }
            if($remaining>0)$failed[]=['assignment_id'=>(int)$a['id'],'teacher'=>$a['teacher_name'],'subject'=>$a['subject_name'],'class'=>$a['class_name'],'remaining'=>$remaining];
        }
        $this->timetables->markGenerated($timetableId);
        return ['placed'=>$placed,'failed'=>$failed,'total_assignments'=>count($assignments)];
    }

    public function publish(int $id): void { $this->timetables->updateStatus($id,'published'); }
}
