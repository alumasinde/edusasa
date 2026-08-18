<?php

declare(strict_types=1);

namespace Modules\Finance\Repositories;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class FeeStructureRepository
{
    public function __construct(private readonly Database $db) {}

    public function structures(): array
    {
        return $this->db->select(
            "SELECT fs.*, COUNT(fsi.id) AS item_count,
                    COALESCE(SUM(fsi.amount),0) AS structure_total
             FROM fee_structures fs
             LEFT JOIN fee_structure_items fsi ON fsi.fee_structure_id=fs.id
             WHERE fs.school_id=:school_id AND fs.deleted_at IS NULL
             GROUP BY fs.id ORDER BY fs.created_at DESC",
            ['school_id'=>Tenant::id()]
        );
    }

    public function categories(): array
    {
        return $this->db->select('SELECT id,name,code FROM fee_categories WHERE school_id=:school_id AND deleted_at IS NULL AND active=1 ORDER BY name', ['school_id'=>Tenant::id()]);
    }

    public function studentsForTarget(?int $classId, ?int $streamId): array
    {
        $sql="SELECT id,admission_no,first_name,last_name,current_class_id,current_stream_id
              FROM students WHERE school_id=:school_id AND deleted_at IS NULL AND status='active'";
        $params=['school_id'=>Tenant::id()];
        if($classId!==null){$sql.=' AND current_class_id=:class_id';$params['class_id']=$classId;}
        if($streamId!==null){$sql.=' AND current_stream_id=:stream_id';$params['stream_id']=$streamId;}
        return $this->db->select($sql.' ORDER BY admission_no', $params);
    }

    public function createStructure(string $name, ?int $classId, ?int $streamId, ?int $academicYearId, ?int $termId, array $items, ?int $userId): int
    {
        if(trim($name)==='') throw new RuntimeException('Fee structure name is required.');
        if(!$items) throw new RuntimeException('Add at least one fee item.');
        $schoolId=Tenant::id();
        return (int)$this->db->transaction(function() use($schoolId,$name,$classId,$streamId,$academicYearId,$termId,$items,$userId){
            $id=(int)$this->db->insert('INSERT INTO fee_structures (school_id,name,academic_year_id,term_id,target_class_id,target_stream_id,status,created_by) VALUES (:school_id,:name,:academic_year_id,:term_id,:class_id,:stream_id,\'draft\',:created_by)',[
                'school_id'=>$schoolId,'name'=>trim($name),'academic_year_id'=>$academicYearId,'term_id'=>$termId,'class_id'=>$classId,'stream_id'=>$streamId,'created_by'=>$userId
            ]);
            $total=0;
            foreach($items as $item){
                $amount=(float)($item['amount']??0); if($amount<=0) throw new RuntimeException('Fee amounts must be greater than zero.');
                $categoryId=(int)($item['category_id']??0); if($categoryId<1) throw new RuntimeException('Each fee item needs a category.');
                $category=$this->db->selectOne('SELECT id FROM fee_categories WHERE id=:id AND school_id=:school_id AND deleted_at IS NULL',['id'=>$categoryId,'school_id'=>$schoolId]);
                if(!$category) throw new RuntimeException('Invalid fee category.');
                $this->db->insert('INSERT INTO fee_structure_items (fee_structure_id,category_id,name,amount,mandatory) VALUES (:structure_id,:category_id,:name,:amount,:mandatory)',[
                    'structure_id'=>$id,'category_id'=>$categoryId,'name'=>trim((string)$item['name']),'amount'=>$amount,'mandatory'=>!empty($item['mandatory'])?1:0
                ]); $total+=$amount;
            }
            return $id;
        });
    }

    public function publish(int $structureId): void
    {
        $this->db->execute("UPDATE fee_structures SET status='published' WHERE id=:id AND school_id=:school_id AND deleted_at IS NULL",['id'=>$structureId,'school_id'=>Tenant::id()]);
    }

    public function generateInvoices(int $structureId,string $invoiceDate,?string $dueDate,string $prefix,?int $userId): array
    {
        $schoolId=Tenant::id();
        $structure=$this->db->selectOne("SELECT * FROM fee_structures WHERE id=:id AND school_id=:school_id AND status='published' AND deleted_at IS NULL",['id'=>$structureId,'school_id'=>$schoolId]);
        if(!$structure) throw new RuntimeException('Published fee structure not found.');
        $items=$this->db->select('SELECT * FROM fee_structure_items WHERE fee_structure_id=:id ORDER BY id',['id'=>$structureId]);
        if(!$items) throw new RuntimeException('The fee structure has no items.');
        $students=$this->studentsForTarget($structure['target_class_id']!==null?(int)$structure['target_class_id']:null,$structure['target_stream_id']!==null?(int)$structure['target_stream_id']:null);
        if(!$students) throw new RuntimeException('No active students match this fee structure.');
        return (array)$this->db->transaction(function() use($schoolId,$structure,$items,$students,$invoiceDate,$dueDate,$prefix,$userId){
            $batchId=(int)$this->db->insert('INSERT INTO fee_billing_batches (school_id,fee_structure_id,invoice_date,due_date,invoice_prefix,students_targeted,created_by) VALUES (:school_id,:structure_id,:invoice_date,:due_date,:prefix,:targeted,:created_by)',[
                'school_id'=>$schoolId,'structure_id'=>$structure['id'],'invoice_date'=>$invoiceDate,'due_date'=>$dueDate,'prefix'=>$prefix,'targeted'=>count($students),'created_by'=>$userId
            ]);
            $created=0;$total=0;$sequence=1;
            foreach($students as $student){
                $existing=$this->db->selectOne("SELECT id FROM fee_invoices WHERE school_id=:school_id AND student_id=:student_id AND invoice_date=:invoice_date AND notes LIKE :batch LIMIT 1",['school_id'=>$schoolId,'student_id'=>$student['id'],'invoice_date'=>$invoiceDate,'batch'=>'%BATCH:'.$batchId.'%']);
                if($existing) continue;
                $invoiceNo=$prefix.'-'.date('Ymd',strtotime($invoiceDate)).'-'.$batchId.'-'.str_pad((string)$sequence,5,'0',STR_PAD_LEFT);$sequence++;
                $subtotal=0;
                foreach($items as $item){$subtotal+=(float)$item['amount'];}
                $invoiceId=(int)$this->db->insert("INSERT INTO fee_invoices (school_id,student_id,invoice_no,invoice_date,due_date,status,subtotal,total,balance,notes,created_by) VALUES (:school_id,:student_id,:invoice_no,:invoice_date,:due_date,'issued',:subtotal,:total,:balance,:notes,:created_by)",[
                    'school_id'=>$schoolId,'student_id'=>$student['id'],'invoice_no'=>$invoiceNo,'invoice_date'=>$invoiceDate,'due_date'=>$dueDate,'subtotal'=>$subtotal,'total'=>$subtotal,'balance'=>$subtotal,'notes'=>'Generated from fee structure #'.$structure['id'].'; BATCH:'.$batchId,'created_by'=>$userId
                ]);
                foreach($items as $item){$this->db->insert('INSERT INTO fee_invoice_items (invoice_id,category_id,description,quantity,unit_amount,amount) VALUES (:invoice_id,:category_id,:description,1,:unit_amount,:amount)',[
                    'invoice_id'=>$invoiceId,'category_id'=>$item['category_id'],'description'=>$item['name'],'unit_amount'=>$item['amount'],'amount'=>$item['amount']
                ]);}
                $this->db->insert('INSERT INTO fee_billing_batch_students (batch_id,student_id,invoice_id) VALUES (:batch_id,:student_id,:invoice_id)',['batch_id'=>$batchId,'student_id'=>$student['id'],'invoice_id'=>$invoiceId]);
                $created++;$total+=$subtotal;
            }
            $this->db->execute("UPDATE fee_billing_batches SET invoices_created=:created,total_billed=:total,status='completed',completed_at=NOW() WHERE id=:id AND school_id=:school_id",['created'=>$created,'total'=>$total,'id'=>$batchId,'school_id'=>$schoolId]);
            return ['batch_id'=>$batchId,'students_targeted'=>count($students),'invoices_created'=>$created,'total_billed'=>$total];
        });
    }
}
