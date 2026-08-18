<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use App\Core\Database;
use RuntimeException;

final class ReceiptService
{
    public function __construct(private readonly Database $db) {}

    public function issue(int $schoolId,int $paymentId): array
    {
        $existing=$this->db->selectOne('SELECT * FROM fee_receipts WHERE school_id=:school_id AND payment_id=:payment_id',['school_id'=>$schoolId,'payment_id'=>$paymentId]);
        if($existing)return $existing;
        $payment=$this->db->selectOne('SELECT p.*,s.admission_no,s.first_name,s.last_name,s.school_id,i.invoice_no FROM fee_payments p INNER JOIN students s ON s.id=p.student_id AND s.school_id=p.school_id LEFT JOIN fee_payment_allocations a ON a.payment_id=p.id LEFT JOIN fee_invoices i ON i.id=a.invoice_id WHERE p.id=:id AND p.school_id=:school_id LIMIT 1',['id'=>$paymentId,'school_id'=>$schoolId]);
        if(!$payment)throw new RuntimeException('Payment not found for receipt.');
        $school=$this->db->selectOne('SELECT id,name,email,phone FROM schools WHERE id=:id',['id'=>$schoolId]);
        if(!$school)throw new RuntimeException('School not found.');
        $snapshot=json_encode(['school'=>['id'=>(int)$school['id'],'name'=>$school['name'],'email'=>$school['email'],'phone'=>$school['phone']],'student'=>['id'=>(int)$payment['student_id'],'admission_no'=>$payment['admission_no'],'name'=>trim($payment['first_name'].' '.$payment['last_name'])],'invoice_no'=>$payment['invoice_no']??null,'payment'=>['amount'=>(float)$payment['amount'],'method'=>$payment['method'],'reference'=>$payment['reference'],'payer_name'=>$payment['payer_name'],'date'=>$payment['payment_date']]],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        try{$id=(int)$this->db->insert('INSERT INTO fee_receipts (school_id,payment_id,student_id,receipt_no,amount,currency,method,reference,payer_name,snapshot_json) VALUES (:school_id,:payment_id,:student_id,:receipt_no,:amount,:currency,:method,:reference,:payer_name,:snapshot_json)',['school_id'=>$schoolId,'payment_id'=>$paymentId,'student_id'=>$payment['student_id'],'receipt_no'=>$payment['receipt_no'],'amount'=>$payment['amount'],'currency'=>'KES','method'=>$payment['method'],'reference'=>$payment['reference'],'payer_name'=>$payment['payer_name'],'snapshot_json'=>$snapshot]);}catch(\PDOException $e){if((int)($e->errorInfo[1]??0)===1062){return $this->db->selectOne('SELECT * FROM fee_receipts WHERE school_id=:school_id AND payment_id=:payment_id',['school_id'=>$schoolId,'payment_id'=>$paymentId])??throw $e;}throw $e;}
        return $this->db->selectOne('SELECT * FROM fee_receipts WHERE id=:id',['id'=>$id])??throw new RuntimeException('Receipt could not be created.');
    }

    public function get(int $receiptId): ?array
    {
        return $this->db->selectOne('SELECT r.*,s.admission_no,s.first_name,s.last_name,sc.name AS school_name,sc.email AS school_email,sc.phone AS school_phone FROM fee_receipts r INNER JOIN students s ON s.id=r.student_id AND s.school_id=r.school_id INNER JOIN schools sc ON sc.id=r.school_id WHERE r.id=:id',['id'=>$receiptId]);
    }
}
