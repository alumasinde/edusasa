<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Core\Tenant;
use Modules\Finance\Providers\MpesaProvider;
use Modules\Finance\Providers\PaymentProvider;
use Modules\Finance\Repositories\PaymentProviderRepository;
use RuntimeException;

final class PaymentProviderService
{
    public function __construct(private readonly PaymentProviderRepository $repository,private readonly Database $db,private readonly MpesaProvider $mpesa) {}
    public function initiate(array $input): array
    {
        $schoolId=Tenant::id();$studentId=(int)($input['student_id']??0);$channelId=(int)($input['channel_id']??0);$invoiceId=(int)($input['invoice_id']??0);$amount=round((float)($input['amount']??0),2);$phone=trim((string)($input['phone']??''));
        if($studentId<1||$channelId<1||$amount<=0||$phone==='')throw new RuntimeException('Student, payment channel, phone and amount are required.');
        $student=$this->repository->student($schoolId,$studentId);if(!$student)throw new RuntimeException('Student not found in this school.');
        $channel=$this->repository->channel($schoolId,$channelId);if(!$channel)throw new RuntimeException('Payment channel is not active or does not belong to this school.');
        if(strtolower((string)$channel['type'])!=='mpesa')throw new RuntimeException('This payment channel does not support online initiation yet.');
        if(!(int)$channel['allow_parent_payment'])throw new RuntimeException('Online payments are disabled for this channel.');
        $invoice=null;if($invoiceId>0){$invoice=$this->repository->invoice($schoolId,$studentId,$invoiceId);if(!$invoice)throw new RuntimeException('Invoice not found or already settled.');if($amount>(float)$invoice['balance'])throw new RuntimeException('Payment cannot exceed the selected invoice balance.');}
        $token=bin2hex(random_bytes(32));$reference=$invoice['invoice_no']??$student['admission_no'];$userId=Session::get('user_id');
        $transactionId=$this->repository->create(['school_id'=>$schoolId,'channel_id'=>$channelId,'student_id'=>$studentId,'invoice_id'=>$invoiceId>0?$invoiceId:null,'provider'=>'mpesa','phone'=>$phone,'amount'=>$amount,'currency'=>'KES','account_reference'=>$reference,'callback_token_hash'=>hash('sha256',$token),'request_payload'=>json_encode(['phone'=>$phone,'amount'=>$amount,'invoice_id'=>$invoiceId?:null],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),'created_by'=>$userId===null?null:(int)$userId]);
        $callback=rtrim((string)Config::env('APP_URL',''),'/').'/api/v1/finance/mpesa/callback/'.$schoolId.'/'.$token;if(!str_starts_with($callback,'https://'))throw new RuntimeException('APP_URL must use HTTPS before M-Pesa payments can be enabled.');
        try{$response=$this->provider('mpesa')->initiate(['phone'=>$phone,'amount'=>$amount,'account_reference'=>$reference,'callback_url'=>$callback],$channel);$this->repository->updateInitiated($transactionId,['merchant_request_id'=>(string)($response['MerchantRequestID']??''),'checkout_request_id'=>(string)($response['CheckoutRequestID']??''),'result_code'=>(string)($response['ResponseCode']??''),'result_description'=>(string)($response['ResponseDescription']??''),'response_payload'=>json_encode($response,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);return ['transaction_id'=>$transactionId,'status'=>'pending','message'=>(string)($response['CustomerMessage']??'STK Push sent to the phone.')];}catch(\Throwable $e){$this->repository->markFailed($transactionId,'INITIATION_ERROR',$e->getMessage());throw $e;}
    }
    public function callback(int $schoolId,string $token,array $payload): void
    {
        if($schoolId<1||$token==='')throw new RuntimeException('Invalid payment callback.');$hash=hash('sha256',$token);$transaction=$this->repository->callbackTransaction($schoolId,$hash);if(!$transaction)throw new RuntimeException('Invalid payment callback.');
        $result=(array)($payload['Body']['stkCallback']??[]);$resultCode=(string)($result['ResultCode']??'');$resultDesc=(string)($result['ResultDesc']??'');$checkout=(string)($result['CheckoutRequestID']??'');$eventKey=$checkout!==''?$checkout:hash('sha256',json_encode($payload));if($this->repository->eventProcessed('mpesa',$eventKey))return;
        $eventId=$this->repository->recordEvent((int)$transaction['id'],'mpesa',$eventKey,$payload);if($eventId<1)return;
        try{$this->db->transaction(function()use($transaction,$result,$resultCode,$resultDesc,$payload,$eventId){
            if($resultCode!=='0'){$this->repository->markCallbackFailed((int)$transaction['id'],$resultCode,$resultDesc,$payload);$this->repository->markEventProcessed($eventId);return;}
            $items=(array)($result['CallbackMetadata']['Item']??[]);$meta=[];foreach($items as $item){if(isset($item['Name']))$meta[(string)$item['Name']]=$item['Value']??null;}
            $paidAmount=round((float)($meta['Amount']??0),2);$receipt=(string)($meta['MpesaReceiptNumber']??'');
            if($receipt===''||$paidAmount<=0)throw new RuntimeException('M-Pesa callback did not contain a valid payment result.');if(abs($paidAmount-(float)$transaction['amount'])>0.01)throw new RuntimeException('M-Pesa callback amount does not match the payment request.');
            $paymentId=$this->repository->createConfirmedPayment(['school_id'=>(int)$transaction['school_id'],'student_id'=>(int)$transaction['student_id'],'receipt_no'=>'MP-'.$receipt,'payment_date'=>date('Y-m-d'),'amount'=>$paidAmount,'method'=>'M-Pesa','reference'=>$receipt,'payer_name'=>null,'created_by'=>$transaction['created_by']]);
            if(!empty($transaction['invoice_id'])){$invoice=$this->repository->invoice((int)$transaction['school_id'],(int)$transaction['student_id'],(int)$transaction['invoice_id']);if(!$invoice)throw new RuntimeException('The selected invoice is no longer payable.');if($paidAmount>(float)$invoice['balance'])throw new RuntimeException('The confirmed payment exceeds the invoice balance.');$this->repository->allocate($paymentId,(int)$invoice['id'],$paidAmount);}
            $this->repository->markPaid((int)$transaction['id'],['provider_reference'=>$receipt,'result_code'=>$resultCode,'result_description'=>$resultDesc,'callback_payload'=>json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);$this->repository->markEventProcessed($eventId);
        });}catch(\Throwable $e){$this->repository->markEventFailed($eventId,$e->getMessage());throw $e;}
    }
    private function provider(string $provider): PaymentProvider{if($provider==='mpesa')return $this->mpesa;throw new RuntimeException('Unsupported payment provider.');}
}
