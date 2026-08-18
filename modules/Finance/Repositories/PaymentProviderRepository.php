<?php

declare(strict_types=1);

namespace Modules\Finance\Repositories;

use App\Core\Database;
use RuntimeException;

final class PaymentProviderRepository
{
    public function __construct(private readonly Database $db) {}

    public function channel(int $schoolId,int $channelId): ?array
    {
        return $this->db->selectOne('SELECT * FROM school_payment_channels WHERE id=:id AND school_id=:school_id AND is_active=1',['id'=>$channelId,'school_id'=>$schoolId]);
    }

    public function student(int $schoolId,int $studentId): ?array
    {
        return $this->db->selectOne('SELECT id,admission_no,first_name,last_name FROM students WHERE id=:id AND school_id=:school_id AND deleted_at IS NULL AND status=\'active\'',['id'=>$studentId,'school_id'=>$schoolId]);
    }

    public function invoice(int $schoolId,int $studentId,int $invoiceId): ?array
    {
        return $this->db->selectOne("SELECT id,invoice_no,total,balance,status FROM fee_invoices WHERE id=:id AND school_id=:school_id AND student_id=:student_id AND deleted_at IS NULL AND status IN ('issued','partially_paid','overdue') AND balance>0 FOR UPDATE",['id'=>$invoiceId,'school_id'=>$schoolId,'student_id'=>$studentId]);
    }

    public function create(array $data): int
    {
        return (int)$this->db->insert('INSERT INTO payment_provider_transactions (school_id,channel_id,student_id,invoice_id,provider,phone,amount,currency,account_reference,status,callback_token_hash,request_payload,created_by) VALUES (:school_id,:channel_id,:student_id,:invoice_id,:provider,:phone,:amount,:currency,:account_reference,\'initiated\',:callback_token_hash,:request_payload,:created_by)',$data);
    }

    public function transaction(int $schoolId,int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM payment_provider_transactions WHERE id=:id AND school_id=:school_id FOR UPDATE',['id'=>$id,'school_id'=>$schoolId]);
    }

    public function callbackTransaction(int $schoolId,string $tokenHash): ?array
    {
        return $this->db->selectOne('SELECT * FROM payment_provider_transactions WHERE school_id=:school_id AND callback_token_hash=:hash FOR UPDATE',['school_id'=>$schoolId,'hash'=>$tokenHash]);
    }

    public function updateInitiated(int $id,array $data): void
    {
        $this->db->execute('UPDATE payment_provider_transactions SET status=\'pending\',merchant_request_id=:merchant_request_id,checkout_request_id=:checkout_request_id,result_code=:result_code,result_description=:result_description,response_payload=:response_payload WHERE id=:id',['id'=>$id]+$data);
    }

    public function markFailed(int $id,string $code,string $description,array $response=[]): void
    {
        $this->db->execute('UPDATE payment_provider_transactions SET status=\'failed\',result_code=:code,result_description=:description,response_payload=:payload WHERE id=:id',['id'=>$id,'code'=>$code,'description'=>$description,'payload'=>json_encode($response,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);
    }

    public function eventExists(string $provider,string $eventKey): bool
    {
        return $this->db->selectOne('SELECT id FROM payment_provider_events WHERE provider=:provider AND event_key=:event_key',['provider'=>$provider,'event_key'=>$eventKey])!==null;
    }

    public function recordEvent(?int $transactionId,string $provider,string $eventKey,array $payload): int
    {
        try{return (int)$this->db->insert('INSERT INTO payment_provider_events (transaction_id,provider,event_key,payload) VALUES (:transaction_id,:provider,:event_key,:payload)',['transaction_id'=>$transactionId,'provider'=>$provider,'event_key'=>$eventKey,'payload'=>json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);}catch(\PDOException $e){if((int)$e->errorInfo[1]===1062)return 0;throw $e;}
    }

    public function markEventProcessed(int $id): void{$this->db->execute("UPDATE payment_provider_events SET status='processed',processed_at=NOW() WHERE id=:id",['id'=>$id]);}
    public function markEventFailed(int $id,string $error): void{$this->db->execute("UPDATE payment_provider_events SET status='failed',error_message=:error WHERE id=:id",['id'=>$id,'error'=>$error]);}

    public function markPaid(int $id,array $data): void
    {
        $this->db->execute("UPDATE payment_provider_transactions SET status='paid',provider_reference=:provider_reference,result_code=:result_code,result_description=:result_description,callback_payload=:callback_payload,paid_at=NOW() WHERE id=:id",['id'=>$id]+$data);
    }

    public function markCallbackFailed(int $id,string $code,string $description,array $payload): void
    {
        $this->db->execute("UPDATE payment_provider_transactions SET status='failed',result_code=:code,result_description=:description,callback_payload=:payload WHERE id=:id",['id'=>$id,'code'=>$code,'description'=>$description,'payload'=>json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);
    }

    public function createConfirmedPayment(array $data): int
    {
        return (int)$this->db->insert('INSERT INTO fee_payments (school_id,student_id,receipt_no,payment_date,amount,method,reference,payer_name,status,created_by) VALUES (:school_id,:student_id,:receipt_no,:payment_date,:amount,:method,:reference,:payer_name,\'confirmed\',:created_by)',$data);
    }

    public function allocate(int $paymentId,int $invoiceId,float $amount): void
    {
        $this->db->insert('INSERT INTO fee_payment_allocations (payment_id,invoice_id,amount) VALUES (:payment_id,:invoice_id,:amount)',['payment_id'=>$paymentId,'invoice_id'=>$invoiceId,'amount'=>$amount]);
        $this->db->execute("UPDATE fee_invoices SET paid_amount=paid_amount+:amount,balance=GREATEST(balance-:amount,0),status=CASE WHEN GREATEST(balance-:amount,0)=0 THEN 'paid' ELSE 'partially_paid' END WHERE id=:id",['amount'=>$amount,'id'=>$invoiceId]);
    }
}
