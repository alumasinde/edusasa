<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use App\Core\Database;
use RuntimeException;

final class FinanceAdjustmentService
{
    public function __construct(private readonly Database $db) {}

    public function invoice(int $schoolId, int $invoiceId): ?array
    {
        return $this->db->selectOne("SELECT i.*,s.admission_no,s.first_name,s.last_name FROM fee_invoices i INNER JOIN students s ON s.id=i.student_id AND s.school_id=i.school_id WHERE i.id=:id AND i.school_id=:school_id AND i.deleted_at IS NULL",['id'=>$invoiceId,'school_id'=>$schoolId]);
    }

    public function adjustments(int $schoolId,int $invoiceId): array
    {
        return $this->db->select("SELECT a.*,CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS approved_by_name FROM fee_adjustments a LEFT JOIN users u ON u.id=a.approved_by WHERE a.school_id=:school_id AND a.invoice_id=:invoice_id ORDER BY a.id DESC",['school_id'=>$schoolId,'invoice_id'=>$invoiceId]);
    }

    public function createAdjustment(int $schoolId,int $invoiceId,string $type,float $amount,string $reason,string $reference,?int $userId): int
    {
        if (!in_array($type,['discount','waiver','credit'],true)) throw new RuntimeException('Invalid adjustment type.');
        if ($amount<=0) throw new RuntimeException('Adjustment amount must be greater than zero.');
        if (trim($reason)==='') throw new RuntimeException('A reason is required.');

        return (int)$this->db->transaction(function() use($schoolId,$invoiceId,$type,$amount,$reason,$reference,$userId){
            $invoice=$this->invoice($schoolId,$invoiceId);
            if (!$invoice) throw new RuntimeException('Invoice not found.');
            if (in_array($invoice['status'],['paid','cancelled'],true)) throw new RuntimeException('Paid or cancelled invoices cannot be adjusted.');
            if ($amount>(float)$invoice['balance']) throw new RuntimeException('Adjustment cannot exceed the outstanding balance.');
            $id=(int)$this->db->insert('INSERT INTO fee_adjustments (school_id,student_id,invoice_id,type,amount,reason,reference,status,approved_by,approved_at,created_by) VALUES (:school_id,:student_id,:invoice_id,:type,:amount,:reason,:reference,\'approved\',:approved_by,NOW(),:created_by)',[
                'school_id'=>$schoolId,'student_id'=>$invoice['student_id'],'invoice_id'=>$invoiceId,'type'=>$type,'amount'=>$amount,'reason'=>trim($reason),'reference'=>trim($reference)!==''?trim($reference):null,'approved_by'=>$userId,'created_by'=>$userId
            ]);
            $this->db->execute("UPDATE fee_invoices SET total=GREATEST(total-:amount,0), balance=GREATEST(balance-:amount,0), status=CASE WHEN GREATEST(balance-:amount,0)=0 THEN 'paid' ELSE 'partially_paid' END WHERE id=:id AND school_id=:school_id",['amount'=>$amount,'id'=>$invoiceId,'school_id'=>$schoolId]);
            return $id;
        });
    }

    public function payment(int $schoolId,int $paymentId): ?array
    {
        return $this->db->selectOne("SELECT p.*,s.admission_no,s.first_name,s.last_name,COALESCE(SUM(a.amount),0) AS allocated_amount,COALESCE(SUM(CASE WHEN r.status IN ('requested','approved','processed') THEN r.amount ELSE 0 END),0) AS refunded_amount FROM fee_payments p INNER JOIN students s ON s.id=p.student_id AND s.school_id=p.school_id LEFT JOIN fee_payment_allocations a ON a.payment_id=p.id LEFT JOIN fee_refunds r ON r.payment_id=p.id WHERE p.id=:id AND p.school_id=:school_id GROUP BY p.id,s.admission_no,s.first_name,s.last_name",['id'=>$paymentId,'school_id'=>$schoolId]);
    }

    public function requestRefund(int $schoolId,int $paymentId,float $amount,string $reason,string $reference,?int $userId): int
    {
        if($amount<=0||trim($reason)==='') throw new RuntimeException('Refund amount and reason are required.');
        return (int)$this->db->transaction(function()use($schoolId,$paymentId,$amount,$reason,$reference,$userId){
            $payment=$this->payment($schoolId,$paymentId);
            if(!$payment||$payment['status']!=='confirmed') throw new RuntimeException('Only confirmed payments can be refunded.');
            $available=(float)$payment['amount']-(float)$payment['refunded_amount'];
            if($amount>$available+0.01) throw new RuntimeException('Refund exceeds the refundable payment amount.');
            return (int)$this->db->insert('INSERT INTO fee_refunds (school_id,payment_id,student_id,invoice_id,amount,reason,reference,status,requested_by) VALUES (:school_id,:payment_id,:student_id,:invoice_id,:amount,:reason,:reference,\'requested\',:requested_by)',[
                'school_id'=>$schoolId,'payment_id'=>$paymentId,'student_id'=>$payment['student_id'],'invoice_id'=>null,'amount'=>$amount,'reason'=>trim($reason),'reference'=>trim($reference)!==''?trim($reference):null,'requested_by'=>$userId
            ]);
        });
    }

    public function approveRefund(int $schoolId,int $refundId,?int $userId): void
    {
        $this->db->execute("UPDATE fee_refunds SET status='approved',approved_by=:user_id WHERE id=:id AND school_id=:school_id AND status='requested'",['user_id'=>$userId,'id'=>$refundId,'school_id'=>$schoolId]);
    }

    public function processRefund(int $schoolId,int $refundId,?int $userId): void
    {
        $this->db->transaction(function()use($schoolId,$refundId,$userId){
            $refund=$this->db->selectOne('SELECT * FROM fee_refunds WHERE id=:id AND school_id=:school_id FOR UPDATE',['id'=>$refundId,'school_id'=>$schoolId]);
            if(!$refund||$refund['status']!=='approved') throw new RuntimeException('Refund must be approved before processing.');
            $payment=$this->payment($schoolId,(int)$refund['payment_id']);
            if(!$payment) throw new RuntimeException('Payment not found.');
            $this->db->execute("UPDATE fee_refunds SET status='processed',processed_by=:user_id,processed_at=NOW() WHERE id=:id",['user_id'=>$userId,'id'=>$refundId]);
            $allocated=(float)$payment['allocated_amount'];
            if($allocated>0){
                $alloc=$this->db->selectOne('SELECT invoice_id,amount FROM fee_payment_allocations WHERE payment_id=:payment_id ORDER BY id DESC LIMIT 1',['payment_id'=>$refund['payment_id']]);
                if($alloc){
                    $restore=min((float)$refund['amount'],(float)$alloc['amount']);
                    $this->db->execute("UPDATE fee_invoices SET paid_amount=GREATEST(paid_amount-:amount,0),balance=LEAST(total,balance+:amount),status=CASE WHEN GREATEST(paid_amount-:amount,0)=0 THEN 'issued' ELSE 'partially_paid' END WHERE id=:id AND school_id=:school_id",['amount'=>$restore,'id'=>$alloc['invoice_id'],'school_id'=>$schoolId]);
                }
            }
        });
    }

    public function refunds(int $schoolId): array
    {
        return $this->db->select('SELECT r.*,p.receipt_no,p.reference AS payment_reference,s.admission_no,s.first_name,s.last_name FROM fee_refunds r INNER JOIN fee_payments p ON p.id=r.payment_id AND p.school_id=r.school_id INNER JOIN students s ON s.id=r.student_id AND s.school_id=r.school_id WHERE r.school_id=:school_id ORDER BY r.id DESC',['school_id'=>$schoolId]);
    }
}
