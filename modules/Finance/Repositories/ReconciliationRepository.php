<?php

declare(strict_types=1);

namespace Modules\Finance\Repositories;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class ReconciliationRepository
{
    public function __construct(private readonly Database $db) {}

    public function summary(string $date): array
    {
        $schoolId = Tenant::id();
        $rows = $this->db->select("SELECT method, COUNT(*) payment_count, COALESCE(SUM(amount),0) total FROM fee_payments WHERE school_id=:school_id AND payment_date=:date AND status='confirmed' GROUP BY method ORDER BY method", ['school_id'=>$schoolId,'date'=>$date]);
        $reconciled = $this->db->select("SELECT * FROM fee_reconciliations WHERE school_id=:school_id AND reconciliation_date=:date AND status<>'void' ORDER BY method", ['school_id'=>$schoolId,'date'=>$date]);
        $total = 0.0;
        foreach ($rows as &$row) { $row['total']=(float)$row['total']; $total += $row['total']; }
        return ['rows'=>$rows,'reconciled'=>$reconciled,'total'=>$total];
    }

    public function payments(string $date, ?string $method = null): array
    {
        $sql="SELECT p.id,p.payment_date,p.receipt_no,p.method,p.reference,p.payer_name,p.amount,p.status,s.admission_no,s.first_name,s.last_name
              FROM fee_payments p JOIN students s ON s.id=p.student_id AND s.school_id=p.school_id
              WHERE p.school_id=:school_id AND p.payment_date=:date";
        $params=['school_id'=>Tenant::id(),'date'=>$date];
        if ($method !== null && $method !== '') { $sql.=' AND p.method=:method'; $params['method']=$method; }
        return $this->db->select($sql.' ORDER BY p.id DESC LIMIT 500',$params);
    }

    public function save(int $id, string $date, string $method, float $actual, string $notes, ?int $userId): int
    {
        if ($actual < 0) throw new RuntimeException('Actual amount cannot be negative.');
        $schoolId=Tenant::id();
        $expected=$this->expected($date,$method);
        $variance=round($actual-$expected,2);
        if ($id>0) {
            $existing=$this->db->selectOne('SELECT id,status FROM fee_reconciliations WHERE id=:id AND school_id=:school_id FOR UPDATE',['id'=>$id,'school_id'=>$schoolId]);
            if(!$existing) throw new RuntimeException('Reconciliation not found.');
            if($existing['status']==='reconciled') throw new RuntimeException('A reconciled record cannot be edited.');
            $this->db->execute('UPDATE fee_reconciliations SET actual_amount=:actual,variance=:variance,notes=:notes,status=\'open\' WHERE id=:id AND school_id=:school_id',['actual'=>$actual,'variance'=>$variance,'notes'=>$notes?:null,'id'=>$id,'school_id'=>$schoolId]);
            return $id;
        }
        return (int)$this->db->insert('INSERT INTO fee_reconciliations (school_id,reconciliation_date,method,expected_amount,actual_amount,variance,notes,created_by) VALUES (:school_id,:date,:method,:expected,:actual,:variance,:notes,:created_by)',['school_id'=>$schoolId,'date'=>$date,'method'=>$method,'expected'=>$expected,'actual'=>$actual,'variance'=>$variance,'notes'=>$notes?:null,'created_by'=>$userId]);
    }

    public function reconcile(int $id, ?int $userId): void
    {
        $row=$this->db->selectOne('SELECT id,actual_amount,expected_amount,status FROM fee_reconciliations WHERE id=:id AND school_id=:school_id FOR UPDATE',['id'=>$id,'school_id'=>Tenant::id()]);
        if(!$row) throw new RuntimeException('Reconciliation not found.');
        if($row['status']==='reconciled') return;
        $this->db->execute("UPDATE fee_reconciliations SET status='reconciled',reconciled_by=:user_id,reconciled_at=NOW() WHERE id=:id AND school_id=:school_id",['user_id'=>$userId,'id'=>$id,'school_id'=>Tenant::id()]);
    }

    private function expected(string $date,string $method): float
    {
        $row=$this->db->selectOne("SELECT COALESCE(SUM(amount),0) total FROM fee_payments WHERE school_id=:school_id AND payment_date=:date AND method=:method AND status='confirmed'",['school_id'=>Tenant::id(),'date'=>$date,'method'=>$method]);
        return round((float)($row['total']??0),2);
    }
}
