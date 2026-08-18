<?php

declare(strict_types=1);

namespace Modules\Finance\Repositories;

use App\Core\Database;
use App\Core\Tenant;
use RuntimeException;

final class FinanceRepository
{
    public function __construct(private readonly Database $db) {}

    public function dashboard(): array
    {
        $schoolId = Tenant::id();
        $invoice = $this->db->selectOne('SELECT COUNT(*) invoices, COALESCE(SUM(total),0) billed, COALESCE(SUM(paid_amount),0) paid, COALESCE(SUM(balance),0) outstanding FROM fee_invoices WHERE school_id=:school_id AND deleted_at IS NULL AND status NOT IN (\'draft\',\'void\')', ['school_id'=>$schoolId]) ?? [];
        $payments = $this->db->selectOne('SELECT COUNT(*) count, COALESCE(SUM(amount),0) total FROM fee_payments WHERE school_id=:school_id AND status=\'confirmed\'', ['school_id'=>$schoolId]) ?? [];
        return [
            'invoices'=>(int)($invoice['invoices']??0), 'billed'=>(float)($invoice['billed']??0),
            'paid'=>(float)($invoice['paid']??0), 'outstanding'=>(float)($invoice['outstanding']??0),
            'payments'=>(int)($payments['count']??0), 'payment_total'=>(float)($payments['total']??0),
        ];
    }

    public function students(string $search = ''): array
    {
        $sql='SELECT id, admission_no, first_name, last_name FROM students WHERE school_id=:school_id AND deleted_at IS NULL AND status=\'active\'';
        $params=['school_id'=>Tenant::id()];
        if ($search !== '') { $sql.=' AND (admission_no LIKE :search OR first_name LIKE :search OR last_name LIKE :search)'; $params['search']='%'.$search.'%'; }
        $sql.=' ORDER BY first_name,last_name LIMIT 100';
        return $this->db->select($sql,$params);
    }

    public function categories(): array
    {
        return $this->db->select('SELECT * FROM fee_categories WHERE school_id=:school_id AND deleted_at IS NULL ORDER BY name', ['school_id'=>Tenant::id()]);
    }

    public function createCategory(string $name, string $code, string $description): int
    {
        return (int)$this->db->insert('INSERT INTO fee_categories (school_id,name,code,description) VALUES (:school_id,:name,:code,:description)', [
            'school_id'=>Tenant::id(),'name'=>$name,'code'=>$code,'description'=>$description
        ]);
    }

    public function createInvoice(int $studentId, string $invoiceNo, string $invoiceDate, ?string $dueDate, array $items, float $discount, ?int $userId): int
    {
        $schoolId=Tenant::id();
        $student=$this->db->selectOne('SELECT id FROM students WHERE id=:id AND school_id=:school_id AND deleted_at IS NULL', ['id'=>$studentId,'school_id'=>$schoolId]);
        if (!$student) throw new RuntimeException('Student not found in this school.');
        if (!$items) throw new RuntimeException('At least one invoice item is required.');
        return (int)$this->db->transaction(function() use ($schoolId,$studentId,$invoiceNo,$invoiceDate,$dueDate,$items,$discount,$userId) {
            $subtotal=0; foreach($items as $item){ $amount=(float)$item['quantity']*(float)$item['unit_amount']; if($amount<0) throw new RuntimeException('Invoice amounts cannot be negative.'); $subtotal+=$amount; }
            $discount=max(0.0,$discount); if($discount>$subtotal) throw new RuntimeException('Discount cannot exceed subtotal.');
            $total=$subtotal-$discount;
            $id=(int)$this->db->insert('INSERT INTO fee_invoices (school_id,student_id,invoice_no,invoice_date,due_date,status,subtotal,discount,total,balance,created_by) VALUES (:school_id,:student_id,:invoice_no,:invoice_date,:due_date,\'issued\',:subtotal,:discount,:total,:balance,:created_by)', [
                'school_id'=>$schoolId,'student_id'=>$studentId,'invoice_no'=>$invoiceNo,'invoice_date'=>$invoiceDate,'due_date'=>$dueDate,'subtotal'=>$subtotal,'discount'=>$discount,'total'=>$total,'balance'=>$total,'created_by'=>$userId
            ]);
            foreach($items as $item){ $amount=(float)$item['quantity']*(float)$item['unit_amount']; $this->db->insert('INSERT INTO fee_invoice_items (invoice_id,category_id,description,quantity,unit_amount,amount) VALUES (:invoice_id,:category_id,:description,:quantity,:unit_amount,:amount)', [
                'invoice_id'=>$id,'category_id'=>($item['category_id']??null),'description'=>trim((string)$item['description']),'quantity'=>(float)$item['quantity'],'unit_amount'=>(float)$item['unit_amount'],'amount'=>$amount
            ]); }
            return $id;
        });
    }

    public function recordPayment(int $studentId, string $receiptNo, string $date, float $amount, string $method, ?string $reference, ?string $payer, ?int $userId, array $allocations=[]): int
    {
        if($amount<=0) throw new RuntimeException('Payment amount must be greater than zero.');
        $schoolId=Tenant::id();
        $student=$this->db->selectOne('SELECT id FROM students WHERE id=:id AND school_id=:school_id AND deleted_at IS NULL', ['id'=>$studentId,'school_id'=>$schoolId]);
        if(!$student) throw new RuntimeException('Student not found in this school.');
        return (int)$this->db->transaction(function() use($schoolId,$studentId,$receiptNo,$date,$amount,$method,$reference,$payer,$userId,$allocations){
            $paymentId=(int)$this->db->insert('INSERT INTO fee_payments (school_id,student_id,receipt_no,payment_date,amount,method,reference,payer_name,created_by) VALUES (:school_id,:student_id,:receipt_no,:payment_date,:amount,:method,:reference,:payer_name,:created_by)', [
                'school_id'=>$schoolId,'student_id'=>$studentId,'receipt_no'=>$receiptNo,'payment_date'=>$date,'amount'=>$amount,'method'=>$method,'reference'=>$reference,'payer_name'=>$payer,'created_by'=>$userId
            ]);
            $remaining=$amount;
            foreach($allocations as $allocation){
                $invoice=$this->db->selectOne('SELECT id,balance FROM fee_invoices WHERE id=:id AND school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL AND status NOT IN (\'void\',\'paid\') FOR UPDATE', ['id'=>(int)$allocation['invoice_id'],'school_id'=>$schoolId,'student_id'=>$studentId]);
                if(!$invoice) throw new RuntimeException('Invoice is invalid for this student.');
                $requested=min((float)$allocation['amount'],$remaining,(float)$invoice['balance']);
                if($requested<=0) continue;
                $this->db->insert('INSERT INTO fee_payment_allocations (payment_id,invoice_id,amount) VALUES (:payment_id,:invoice_id,:amount)', ['payment_id'=>$paymentId,'invoice_id'=>$invoice['id'],'amount'=>$requested]);
                $this->db->execute('UPDATE fee_invoices SET paid_amount=paid_amount+:amount,balance=GREATEST(balance-:amount,0),status=CASE WHEN GREATEST(balance-:amount,0)=0 THEN \'paid\' ELSE \'partially_paid\' END WHERE id=:id AND school_id=:school_id', ['amount'=>$requested,'id'=>$invoice['id'],'school_id'=>$schoolId]);
                $remaining-=$requested; if($remaining<=0) break;
            }
            if($remaining>0){
                $invoice=$this->db->selectOne('SELECT id,balance FROM fee_invoices WHERE school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL AND status IN (\'issued\',\'partially_paid\',\'overdue\') AND balance>0 ORDER BY due_date IS NULL,due_date,id LIMIT 1 FOR UPDATE', ['school_id'=>$schoolId,'student_id'=>$studentId]);
                if($invoice){ $applied=min($remaining,(float)$invoice['balance']); $this->db->insert('INSERT INTO fee_payment_allocations (payment_id,invoice_id,amount) VALUES (:payment_id,:invoice_id,:amount)', ['payment_id'=>$paymentId,'invoice_id'=>$invoice['id'],'amount'=>$applied]); $this->db->execute('UPDATE fee_invoices SET paid_amount=paid_amount+:amount,balance=GREATEST(balance-:amount,0),status=CASE WHEN GREATEST(balance-:amount,0)=0 THEN \'paid\' ELSE \'partially_paid\' END WHERE id=:id AND school_id=:school_id', ['amount'=>$applied,'id'=>$invoice['id'],'school_id'=>$schoolId]); }
            }
            return $paymentId;
        });
    }

    public function invoices(string $search=''): array
    {
        $sql='SELECT i.*,s.admission_no,s.first_name,s.last_name FROM fee_invoices i JOIN students s ON s.id=i.student_id AND s.school_id=i.school_id WHERE i.school_id=:school_id AND i.deleted_at IS NULL'; $params=['school_id'=>Tenant::id()];
        if($search!==''){ $sql.=' AND (i.invoice_no LIKE :search OR s.admission_no LIKE :search OR s.first_name LIKE :search OR s.last_name LIKE :search)'; $params['search']='%'.$search.'%'; }
        return $this->db->select($sql.' ORDER BY i.invoice_date DESC,i.id DESC LIMIT 200',$params);
    }
}
