<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use App\Core\Database;
use RuntimeException;

final class FinanceControlService
{
    public function __construct(private readonly Database $db) {}

    public function periods(int $schoolId): array
    {
        return $this->db->select('SELECT * FROM finance_periods WHERE school_id=:school_id ORDER BY starts_on DESC', ['school_id'=>$schoolId]);
    }

    public function createPeriod(int $schoolId,string $name,string $starts,string $ends,?int $userId): int
    {
        if ($schoolId<1 || trim($name)==='' || !$this->date($starts) || !$this->date($ends) || $starts>$ends) throw new RuntimeException('Invalid finance period.');
        $overlap=$this->db->selectOne('SELECT id FROM finance_periods WHERE school_id=:school_id AND starts_on<=:ends_on AND ends_on>=:starts_on LIMIT 1',['school_id'=>$schoolId,'starts_on'=>$starts,'ends_on'=>$ends]);
        if ($overlap) throw new RuntimeException('Finance period overlaps an existing period.');
        return (int)$this->db->insert('INSERT INTO finance_periods (school_id,name,starts_on,ends_on,created_by) VALUES (:school_id,:name,:starts_on,:ends_on,:created_by)',['school_id'=>$schoolId,'name'=>trim($name),'starts_on'=>$starts,'ends_on'=>$ends,'created_by'=>$userId]);
    }

    public function lockPeriod(int $schoolId,int $periodId,?int $userId): void
    {
        $this->db->transaction(function() use($schoolId,$periodId,$userId){
            $p=$this->period($schoolId,$periodId);
            if (!$p || $p['status']!=='open') throw new RuntimeException('Only open periods can be locked.');
            $this->db->execute("UPDATE finance_periods SET status='locked',locked_by=:user_id,locked_at=NOW() WHERE id=:id AND school_id=:school_id AND status='open'",['id'=>$periodId,'school_id'=>$schoolId,'user_id'=>$userId]);
            $this->audit($schoolId,'period_lock',null,null,$periodId,null,'Finance period locked',null,$userId);
        });
    }

    public function closePeriod(int $schoolId,int $periodId,?int $userId): void
    {
        $this->db->transaction(function() use($schoolId,$periodId,$userId){
            $p=$this->period($schoolId,$periodId);
            if (!$p || $p['status']!=='locked') throw new RuntimeException('Only locked periods can be closed.');
            $this->db->execute("UPDATE finance_periods SET status='closed',closed_by=:user_id,closed_at=NOW() WHERE id=:id AND school_id=:school_id AND status='locked'",['id'=>$periodId,'school_id'=>$schoolId,'user_id'=>$userId]);
            $this->audit($schoolId,'period_close',null,null,$periodId,null,'Finance period closed',null,$userId);
        });
    }

    public function reversePayment(int $schoolId,int $paymentId,string $reason,?int $userId): void
    {
        if (trim($reason)==='') throw new RuntimeException('A reversal reason is required.');
        $this->db->transaction(function() use($schoolId,$paymentId,$reason,$userId){
            $payment=$this->db->selectOne('SELECT * FROM fee_payments WHERE id=:id AND school_id=:school_id FOR UPDATE',['id'=>$paymentId,'school_id'=>$schoolId]);
            if (!$payment) throw new RuntimeException('Payment not found.');
            if ($payment['status']!=='confirmed') throw new RuntimeException('Only confirmed payments can be reversed.');
            $this->assertOpen($schoolId,(string)$payment['payment_date']);
            $allocated=$this->db->selectOne('SELECT COALESCE(SUM(amount),0) amount FROM fee_payment_allocations WHERE payment_id=:payment_id',['payment_id'=>$paymentId]);
            $this->db->execute("UPDATE fee_payments SET status='reversed' WHERE id=:id AND school_id=:school_id AND status='confirmed'",['id'=>$paymentId,'school_id'=>$schoolId]);
            foreach ($this->db->select('SELECT invoice_id,amount FROM fee_payment_allocations WHERE payment_id=:payment_id',['payment_id'=>$paymentId]) as $a) {
                $this->db->execute('UPDATE fee_invoices SET paid_amount=GREATEST(0,paid_amount-:amount),balance=LEAST(total,GREATEST(0,balance+:amount)),status=CASE WHEN paid_amount-:amount<=0 THEN "issued" ELSE "partially_paid" END WHERE id=:id AND school_id=:school_id',['amount'=>(float)$a['amount'],'id'=>(int)$a['invoice_id'],'school_id'=>$schoolId]);
            }
            $this->audit($schoolId,'payment_reversal',$paymentId,null,null,(float)$allocated['amount'],$reason,null,$userId);
        });
    }

    public function voidInvoice(int $schoolId,int $invoiceId,string $reason,?int $userId): void
    {
        if (trim($reason)==='') throw new RuntimeException('A void reason is required.');
        $this->db->transaction(function() use($schoolId,$invoiceId,$reason,$userId){
            $invoice=$this->db->selectOne('SELECT * FROM fee_invoices WHERE id=:id AND school_id=:school_id AND deleted_at IS NULL FOR UPDATE',['id'=>$invoiceId,'school_id'=>$schoolId]);
            if (!$invoice) throw new RuntimeException('Invoice not found.');
            if ((float)$invoice['paid_amount']>0) throw new RuntimeException('Paid invoices cannot be voided. Reverse/refund the payment first.');
            $this->assertOpen($schoolId,(string)$invoice['invoice_date']);
            $this->db->execute("UPDATE fee_invoices SET status='void',balance=0 WHERE id=:id AND school_id=:school_id AND status NOT IN ('paid','void')",['id'=>$invoiceId,'school_id'=>$schoolId]);
            $this->audit($schoolId,'invoice_void',null,$invoiceId,null,(float)$invoice['total'],$reason,null,$userId);
        });
    }

    public function controlLog(int $schoolId): array
    {
        return $this->db->select('SELECT * FROM finance_control_actions WHERE school_id=:school_id ORDER BY created_at DESC LIMIT 200',['school_id'=>$schoolId]);
    }

    private function period(int $schoolId,int $id): ?array { return $this->db->selectOne('SELECT * FROM finance_periods WHERE id=:id AND school_id=:school_id FOR UPDATE',['id'=>$id,'school_id'=>$schoolId]); }
    private function assertOpen(int $schoolId,string $date): void { $p=$this->db->selectOne("SELECT id,status FROM finance_periods WHERE school_id=:school_id AND starts_on<=:d AND ends_on>=:d ORDER BY starts_on DESC LIMIT 1",['school_id'=>$schoolId,'d'=>$date]); if ($p && $p['status']!=='open') throw new RuntimeException('The transaction belongs to a locked or closed finance period.'); }
    private function audit(int $schoolId,string $type,?int $paymentId,?int $invoiceId,?int $periodId,?float $amount,string $reason,?string $reference,?int $userId): void { $this->db->insert('INSERT INTO finance_control_actions (school_id,action_type,payment_id,invoice_id,period_id,amount,reason,reference,status,requested_by,approved_by,processed_by,processed_at) VALUES (:school_id,:action_type,:payment_id,:invoice_id,:period_id,:amount,:reason,:reference,\'processed\',:user_id,:user_id,:user_id,NOW())',['school_id'=>$schoolId,'action_type'=>$type,'payment_id'=>$paymentId,'invoice_id'=>$invoiceId,'period_id'=>$periodId,'amount'=>$amount,'reason'=>$reason,'reference'=>$reference,'user_id'=>$userId]); }
    private function date(string $value): bool { $d=\DateTimeImmutable::createFromFormat('Y-m-d',$value); return $d!==false && $d->format('Y-m-d')===$value; }
}
