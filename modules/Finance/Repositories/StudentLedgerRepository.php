<?php

declare(strict_types=1);

namespace Modules\Finance\Repositories;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class StudentLedgerRepository
{
    public function __construct(private readonly Database $db) {}

    private function student(int $studentId): array
    {
        $student = $this->db->selectOne(
            'SELECT id, admission_no, first_name, last_name, current_class_id, current_stream_id FROM students WHERE id=:id AND school_id=:school_id AND deleted_at IS NULL',
            ['id'=>$studentId,'school_id'=>Tenant::id()]
        );
        if (!$student) throw new RuntimeException('Student not found in this school.');
        return $student;
    }

    public function ledger(int $studentId, ?string $from=null, ?string $to=null): array
    {
        $student=$this->student($studentId); $schoolId=Tenant::id();
        $invoiceSql="SELECT i.id,i.invoice_no,i.invoice_date,i.due_date,i.status,i.total,i.paid_amount,i.balance,
                            ii.description,ii.quantity,ii.unit_amount,ii.amount,fc.name category_name
                     FROM fee_invoices i
                     JOIN fee_invoice_items ii ON ii.invoice_id=i.id
                     LEFT JOIN fee_categories fc ON fc.id=ii.category_id AND fc.school_id=i.school_id
                     WHERE i.school_id=:school_id AND i.student_id=:student_id AND i.deleted_at IS NULL";
        $params=['school_id'=>$schoolId,'student_id'=>$studentId];
        if($from!==null){$invoiceSql.=' AND i.invoice_date>=:from_date';$params['from_date']=$from;}
        if($to!==null){$invoiceSql.=' AND i.invoice_date<=:to_date';$params['to_date']=$to;}
        $invoices=$this->db->select($invoiceSql.' ORDER BY i.invoice_date DESC,i.id DESC,ii.id ASC',$params);

        $paymentSql="SELECT p.id,p.receipt_no,p.payment_date,p.amount,p.method,p.reference,p.payer_name,
                            COALESCE(SUM(pa.amount),0) allocated,
                            p.amount-COALESCE(SUM(pa.amount),0) unallocated
                     FROM fee_payments p
                     LEFT JOIN fee_payment_allocations pa ON pa.payment_id=p.id
                     WHERE p.school_id=:school_id AND p.student_id=:student_id AND p.status='confirmed'";
        $pp=['school_id'=>$schoolId,'student_id'=>$studentId];
        if($from!==null){$paymentSql.=' AND p.payment_date>=:from_date';$pp['from_date']=$from;}
        if($to!==null){$paymentSql.=' AND p.payment_date<=:to_date';$pp['to_date']=$to;}
        $payments=$this->db->select($paymentSql.' GROUP BY p.id ORDER BY p.payment_date DESC,p.id DESC',$pp);

        $summary=$this->db->selectOne(
            "SELECT COUNT(*) invoices,COALESCE(SUM(total),0) billed,COALESCE(SUM(paid_amount),0) paid,COALESCE(SUM(balance),0) outstanding
             FROM fee_invoices WHERE school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL AND status NOT IN ('draft','void')" ,
            ['school_id'=>$schoolId,'student_id'=>$studentId]
        ) ?? [];
        $paymentSummary=$this->db->selectOne(
            "SELECT COUNT(*) payments,COALESCE(SUM(amount),0) received FROM fee_payments WHERE school_id=:school_id AND student_id=:student_id AND status='confirmed'",
            ['school_id'=>$schoolId,'student_id'=>$studentId]
        ) ?? [];
        return ['student'=>$student,'invoices'=>$invoices,'payments'=>$payments,'summary'=>[
            'invoices'=>(int)($summary['invoices']??0),'billed'=>(float)($summary['billed']??0),
            'paid'=>(float)($summary['paid']??0),'outstanding'=>(float)($summary['outstanding']??0),
            'payments'=>(int)($paymentSummary['payments']??0),'received'=>(float)($paymentSummary['received']??0)
        ]];
    }

    public function statement(int $studentId, ?string $from=null, ?string $to=null): array
    {
        $data=$this->ledger($studentId,$from,$to); $events=[];
        foreach($data['invoices'] as $invoice){$events[]=['date'=>$invoice['invoice_date'],'type'=>'invoice','reference'=>$invoice['invoice_no'],'description'=>$invoice['description'],'debit'=>(float)$invoice['amount'],'credit'=>0.0,'status'=>$invoice['status']];}
        foreach($data['payments'] as $payment){$events[]=['date'=>$payment['payment_date'],'type'=>'payment','reference'=>$payment['receipt_no'],'description'=>'Payment — '.strtoupper($payment['method']),'debit'=>0.0,'credit'=>(float)$payment['amount'],'status'=>'confirmed'];}
        usort($events,static fn(array $a,array $b)=>[$a['date'],$a['type']]==[$b['date'],$b['type']]?strcmp($a['reference'],$b['reference']):strcmp($a['date'],$b['date']));
        $running=0.0; foreach($events as &$event){$running += $event['debit']-$event['credit'];$event['balance']=$running;} unset($event);
        $data['events']=$events; $data['closing_balance']=$running; return $data;
    }
}
