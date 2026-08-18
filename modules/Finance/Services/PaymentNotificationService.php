<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use App\Core\Database;
use App\Core\Notifications;
use RuntimeException;

final class PaymentNotificationService
{
    public function __construct(private readonly Database $db) {}

    public function notify(int $schoolId, int $paymentId, int $receiptId): void
    {
        $data = $this->db->selectOne('SELECT p.id,p.amount,p.method,p.reference,p.receipt_no,p.payment_date,s.admission_no,s.first_name,s.last_name,g.email,g.phone,sc.name AS school_name FROM fee_payments p INNER JOIN students s ON s.id=p.student_id AND s.school_id=p.school_id LEFT JOIN student_guardians sg ON sg.student_id=s.id AND sg.is_primary=1 AND sg.deleted_at IS NULL LEFT JOIN guardians g ON g.id=sg.guardian_id INNER JOIN schools sc ON sc.id=p.school_id WHERE p.id=:payment_id AND p.school_id=:school_id',['payment_id'=>$paymentId,'school_id'=>$schoolId]);
        if (!$data) throw new RuntimeException('Payment notification data not found.');

        $message = sprintf('%s payment received: KES %s. Receipt %s. Reference %s.', $data['school_name'], number_format((float)$data['amount'],2), $data['receipt_no'], $data['reference'] ?: 'N/A');
        $payload = ['payment_id'=>$paymentId,'receipt_id'=>$receiptId,'message'=>$message,'student'=>$data['admission_no'],'amount'=>(float)$data['amount']];

        if (!empty($data['email'])) {
            $this->deliver($schoolId,$paymentId,$receiptId,'email',(string)$data['email'],'payment.receipt',['subject'=>'Payment receipt - '.$data['school_name'],'message'=>$message]+$payload);
        } else {
            $this->log($schoolId,$paymentId,$receiptId,'email','unavailable','payment.receipt','skipped',$payload,'Parent email is not configured.');
        }

        if (!empty($data['phone'])) {
            $this->deliver($schoolId,$paymentId,$receiptId,'sms',(string)$data['phone'],'payment.receipt',$payload);
        } else {
            $this->log($schoolId,$paymentId,$receiptId,'sms','unavailable','payment.receipt','skipped',$payload,'Parent phone is not configured.');
        }
    }

    private function deliver(int $schoolId,int $paymentId,int $receiptId,string $channel,string $recipient,string $template,array $payload):void
    {
        $id=$this->log($schoolId,$paymentId,$receiptId,$channel,$recipient,$template,'queued',$payload,null);
        try {
            if($channel==='email') {
                Notifications::send('email',$recipient,$payload);
                $this->db->execute("UPDATE payment_notification_deliveries SET status='sent',sent_at=NOW() WHERE id=:id",['id'=>$id]);
                return;
            }
            // SMS remains provider-neutral until a school/platform SMS provider is configured.
            $this->db->execute("UPDATE payment_notification_deliveries SET status='queued' WHERE id=:id",['id'=>$id]);
        } catch (\Throwable $e) {
            $this->db->execute("UPDATE payment_notification_deliveries SET status='failed',error_message=:error WHERE id=:id",['id'=>$id,'error'=>substr($e->getMessage(),0,500)]);
        }
    }

    private function log(int $schoolId,int $paymentId,int $receiptId,string $channel,string $recipient,string $template,string $status,array $payload,?string $error):int
    {
        return (int)$this->db->insert('INSERT INTO payment_notification_deliveries (school_id,payment_id,receipt_id,channel,recipient,template,status,error_message,payload_json) VALUES (:school_id,:payment_id,:receipt_id,:channel,:recipient,:template,:status,:error_message,:payload_json)',[
            'school_id'=>$schoolId,'payment_id'=>$paymentId,'receipt_id'=>$receiptId,'channel'=>$channel,'recipient'=>$recipient,'template'=>$template,'status'=>$status,'error_message'=>$error,'payload_json'=>json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
        ]);
    }
}
