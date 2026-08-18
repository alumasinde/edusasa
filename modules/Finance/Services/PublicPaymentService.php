<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use App\Core\Config;
use App\Core\Database;
use Modules\Finance\Providers\MpesaProvider;
use Modules\Finance\Providers\PaystackProvider;
use Modules\Finance\Providers\PesapalProvider;
use Modules\Finance\Repositories\PaymentProviderRepository;
use RuntimeException;

final class PublicPaymentService
{
    public function __construct(private readonly PaymentProviderRepository $repository, private readonly Database $db, private readonly MpesaProvider $mpesa, private readonly PaystackProvider $paystack, private readonly PesapalProvider $pesapal) {}

    public function createLink(int $schoolId, int $studentId, int $invoiceId, int $ttl = 86400): string
    { $payload=['s'=>$schoolId,'u'=>$studentId,'i'=>$invoiceId,'e'=>time()+$ttl,'n'=>bin2hex(random_bytes(8))]; return $this->sign($payload); }

    public function decodeLink(string $token): array
    { $parts=explode('.',trim($token),2);if(count($parts)!==2)throw new RuntimeException('Invalid payment link.');$json=base64_decode(strtr($parts[0],'-_','+/'),true);$payload=is_string($json)?json_decode($json,true):null;if(!is_array($payload)||!hash_equals($this->signature($parts[0]),$parts[1])||(int)($payload['e']??0)<time())throw new RuntimeException('Payment link is invalid or expired.');return ['school_id'=>(int)$payload['s'],'student_id'=>(int)$payload['u'],'invoice_id'=>(int)$payload['i']]; }

    public function invoice(string $token): array
    { $scope=$this->decodeLink($token);$invoice=$this->repository->publicInvoice($scope['school_id'],$scope['student_id'],$scope['invoice_id']);if(!$invoice)throw new RuntimeException('Invoice is no longer payable.');return ['scope'=>$scope,'invoice'=>$invoice,'channels'=>$this->repository->onlineChannels($scope['school_id'])]; }

    public function initiate(string $token,array $input): array
    {
        $data=$this->invoice($token);$scope=$data['scope'];$invoice=$data['invoice'];$channelId=(int)($input['channel_id']??0);$amount=round((float)($input['amount']??0),2);
        if($channelId<1||$amount<=0||$amount>(float)$invoice['balance'])throw new RuntimeException('Select a valid payment method and amount.');
        $channel=$this->repository->channel($scope['school_id'],$channelId);if(!$channel||!(int)$channel['allow_parent_payment'])throw new RuntimeException('Payment method is unavailable.');
        $provider=strtolower(trim((string)($channel['provider']??$channel['type'])));if(!in_array($provider,['mpesa','paystack','pesapal'],true))throw new RuntimeException('This payment method does not support online checkout yet.');
        $email=trim((string)($input['email']??''));$phone=trim((string)($input['phone']??''));if($provider==='paystack'&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Email is required for Paystack.');if($provider==='mpesa'&&$phone==='')throw new RuntimeException('Phone number is required for M-Pesa.');
        $raw=bin2hex(random_bytes(32));$base=rtrim((string)Config::env('APP_URL',''),'/');$callback=$base.'/pay/callback/'.$provider.'?tx=0&token='.rawurlencode($raw);if(!str_starts_with($base,'https://'))throw new RuntimeException('APP_URL must use HTTPS before online payments can be enabled.');
        $student=$this->repository->student($scope['school_id'],$scope['student_id']);if(!$student)throw new RuntimeException('Student is no longer active.');
        $tx=$this->repository->create(['school_id'=>$scope['school_id'],'channel_id'=>$channelId,'student_id'=>$scope['student_id'],'invoice_id'=>$scope['invoice_id'],'provider'=>$provider,'phone'=>$phone,'amount'=>$amount,'currency'=>'KES','account_reference'=>$invoice['invoice_no'],'callback_token_hash'=>hash('sha256',$raw),'request_payload'=>json_encode(['email'=>$email,'phone'=>$phone,'invoice_id'=>$scope['invoice_id']],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),'created_by'=>null]);
        $callback=str_replace('tx=0','tx='.$tx,$callback);$request=['id'=>$tx,'school_id'=>$scope['school_id'],'student_id'=>$scope['student_id'],'invoice_id'=>$scope['invoice_id'],'amount'=>$amount,'phone'=>$phone,'email'=>$email,'account_reference'=>$invoice['invoice_no'],'callback_url'=>$callback,'first_name'=>$student['first_name'],'last_name'=>$student['last_name']];
        try{$response=match($provider){'mpesa'=>$this->mpesa->initiate($request,$channel),'paystack'=>$this->paystack->initiate($request,$channel),'pesapal'=>$this->pesapal->initiate($request,$channel)};$this->repository->updateInitiated($tx,['merchant_request_id'=>(string)($response['order_tracking_id']??$response['provider_reference']??''),'checkout_request_id'=>(string)($response['checkout_request_id']??$response['provider_reference']??''),'result_code'=>'0','result_description'=>'Payment initialized','response_payload'=>json_encode($response,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);return ['transaction_id'=>$tx,'status'=>'pending','provider'=>$provider,'authorization_url'=>$response['authorization_url']??null,'message'=>$provider==='mpesa'?'Check the phone and approve the M-Pesa prompt.':'Continue on the secure payment page.','status_token'=>$raw];}catch(\Throwable $e){$this->repository->markFailed($tx,'INITIATION_ERROR',$e->getMessage());throw $e;}
    }

    public function status(int $id,string $token): array
    { $tx=$this->repository->publicTransaction($id,hash('sha256',$token));if(!$tx)throw new RuntimeException('Payment session not found.');return ['status'=>$tx['status'],'message'=>$tx['result_description'],'provider_reference'=>$tx['provider_reference']]; }

    public function paystackWebhook(string $signature,string $rawBody): void
    {
        $event=json_decode($rawBody,true);if(!is_array($event))throw new RuntimeException('Invalid Paystack webhook.');$reference=(string)($event['data']['reference']??'');if($reference==='')return;
        $tx=$this->repository->byExternalReference('paystack',$reference);if(!$tx)return;$channel=$this->repository->channel((int)$tx['school_id'],(int)$tx['channel_id']);if(!$channel)return;$config=json_decode((string)$channel['config_json'],true);$secret=is_array($config)?(string)($config['secret_key']??''):'';if($secret===''||!hash_equals(hash_hmac('sha512',$rawBody,$secret),$signature))throw new RuntimeException('Invalid Paystack webhook signature.');
        $eventKey='paystack:'.$reference.':'.hash('sha256',$rawBody);if($this->repository->eventProcessed('paystack',$eventKey))return;$eventId=$this->repository->recordEvent((int)$tx['id'],'paystack',$eventKey,$event);try{$verified=$this->paystack->verify($reference,$channel);$data=$verified['data']??[];if(($data['status']??'')!=='success'){$this->repository->markCallbackFailed((int)$tx['id'],'NOT_SUCCESS',(string)($data['gateway_response']??'Payment not successful'),$event);$this->repository->markEventProcessed($eventId);return;}if(abs(((float)($data['amount']??0))/100-(float)$tx['amount'])>0.01)throw new RuntimeException('Paystack amount does not match the requested amount.');$this->complete((int)$tx['id'],$tx,$reference,(string)($data['gateway_response']??'Successful'),$event,'Paystack');$this->repository->markEventProcessed($eventId);}catch(\Throwable $e){$this->repository->markEventFailed($eventId,$e->getMessage());throw $e;}
    }

    public function pesapalNotification(array $input): void
    {
        $tracking=(string)($input['OrderTrackingId']??$input['orderTrackingId']??'');$reference=(string)($input['OrderMerchantReference']??$input['orderMerchantReference']??'');if($tracking===''||$reference==='')return;$tx=$this->repository->byExternalReference('pesapal',$reference);if(!$tx)return;$channel=$this->repository->channel((int)$tx['school_id'],(int)$tx['channel_id']);if(!$channel)return;$eventKey='pesapal:'.$tracking;if($this->repository->eventProcessed('pesapal',$eventKey))return;$eventId=$this->repository->recordEvent((int)$tx['id'],'pesapal',$eventKey,$input);try{$status=$this->pesapal->status($tracking,$channel);$description=strtolower((string)($status['payment_status_description']??$status['status_description']??''));$completed=$description==='completed'||(int)($status['status_code']??0)===1;if($completed){$this->complete((int)$tx['id'],$tx,$tracking,'Completed',$status,'Pesapal');}elseif(str_contains($description,'failed')||(int)($status['status_code']??0)===2){$this->repository->markCallbackFailed((int)$tx['id'],'FAILED','Pesapal payment failed',$status);}else{$this->repository->markCallbackFailed((int)$tx['id'],'PENDING','Pesapal payment is still pending',$status);}$this->repository->markEventProcessed($eventId);}catch(\Throwable $e){$this->repository->markEventFailed($eventId,$e->getMessage());throw $e;}
    }

    private function complete(int $id,array $tx,string $reference,string $description,array $payload,string $method): void
    { $this->db->transaction(function()use($id,$tx,$reference,$description,$payload,$method){$invoice=$this->repository->invoice((int)$tx['school_id'],(int)$tx['student_id'],(int)$tx['invoice_id']);if(!$invoice)throw new RuntimeException('Invoice is no longer payable.');$amount=(float)$tx['amount'];if($amount>(float)$invoice['balance'])throw new RuntimeException('Payment exceeds the current invoice balance.');$payment=$this->repository->createConfirmedPayment(['school_id'=>(int)$tx['school_id'],'student_id'=>(int)$tx['student_id'],'receipt_no'=>strtoupper(substr($method,0,3)).'-'.$reference,'payment_date'=>date('Y-m-d'),'amount'=>$amount,'method'=>$method,'reference'=>$reference,'payer_name'=>null,'created_by'=>null]);$this->repository->allocate($payment,(int)$invoice['id'],$amount);$this->repository->markPaid($id,['provider_reference'=>$reference,'result_code'=>'0','result_description'=>$description,'callback_payload'=>json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);}); }

    private function sign(array $payload): string{$raw=rtrim(strtr(base64_encode(json_encode($payload,JSON_UNESCAPED_SLASHES)), '+/', '-_'),'=');return $raw.'.'.$this->signature($raw);}
    private function signature(string $raw): string{$key=(string)Config::env('APP_KEY','');if($key==='')throw new RuntimeException('APP_KEY is required for payment links.');return hash_hmac('sha256',$raw,$key);}
}
