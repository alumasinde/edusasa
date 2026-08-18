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
    {
        $payload = ['s'=>$schoolId,'u'=>$studentId,'i'=>$invoiceId,'e'=>time()+$ttl,'n'=>bin2hex(random_bytes(8))];
        return $this->sign($payload);
    }

    public function decodeLink(string $token): array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) throw new RuntimeException('Invalid payment link.');
        $json = base64_decode(strtr($parts[0], '-_', '+/'), true);
        if ($json === false) throw new RuntimeException('Invalid payment link.');
        $payload = json_decode($json, true);
        if (!is_array($payload) || !hash_equals($this->signature($parts[0]), $parts[1]) || (int)($payload['e']??0) < time()) throw new RuntimeException('Payment link is invalid or expired.');
        return ['school_id'=>(int)$payload['s'],'student_id'=>(int)$payload['u'],'invoice_id'=>(int)$payload['i']];
    }

    public function invoice(string $token): array
    {
        $scope=$this->decodeLink($token);$invoice=$this->repository->publicInvoice($scope['school_id'],$scope['student_id'],$scope['invoice_id']);
        if(!$invoice) throw new RuntimeException('Invoice is no longer payable.');
        return ['scope'=>$scope,'invoice'=>$invoice,'channels'=>$this->repository->onlineChannels($scope['school_id'])];
    }

    public function initiate(string $token, array $input): array
    {
        $data=$this->invoice($token);$scope=$data['scope'];$invoice=$data['invoice'];$channelId=(int)($input['channel_id']??0);$amount=round((float)($input['amount']??0),2);
        if($channelId<1 || $amount<=0 || $amount>(float)$invoice['balance']) throw new RuntimeException('Select a valid payment method and amount.');
        $channel=$this->repository->channel($scope['school_id'],$channelId);if(!$channel || !(int)$channel['allow_parent_payment']) throw new RuntimeException('Payment method is unavailable.');
        $provider=strtolower(trim((string)($channel['provider']??$channel['type'])));if(!in_array($provider,['mpesa','paystack','pesapal'],true)) throw new RuntimeException('This payment method does not support online checkout yet.');
        $email=trim((string)($input['email']??''));$phone=trim((string)($input['phone']??''));
        if($provider==='paystack' && !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email is required for Paystack.');
        if($provider==='mpesa' && $phone==='') throw new RuntimeException('Phone number is required for M-Pesa.');
        $raw=bin2hex(random_bytes(32));$callback=rtrim((string)Config::env('APP_URL',''),'/').'/pay/callback/'.$provider;
        if(!str_starts_with($callback,'https://')) throw new RuntimeException('APP_URL must use HTTPS before online payments can be enabled.');
        $student=$this->repository->student($scope['school_id'],$scope['student_id']);if(!$student)throw new RuntimeException('Student is no longer active.');
        $tx=$this->repository->create(['school_id'=>$scope['school_id'],'channel_id'=>$channelId,'student_id'=>$scope['student_id'],'invoice_id'=>$scope['invoice_id'],'provider'=>$provider,'phone'=>$phone,'amount'=>$amount,'currency'=>'KES','account_reference'=>$invoice['invoice_no'],'callback_token_hash'=>hash('sha256',$raw),'request_payload'=>json_encode(['email'=>$email,'phone'=>$phone,'invoice_id'=>$scope['invoice_id']],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),'created_by'=>null]);
        $request=['id'=>$tx,'school_id'=>$scope['school_id'],'student_id'=>$scope['student_id'],'invoice_id'=>$scope['invoice_id'],'amount'=>$amount,'phone'=>$phone,'email'=>$email,'account_reference'=>$invoice['invoice_no'],'callback_url'=>$callback,'first_name'=>$student['first_name'],'last_name'=>$student['last_name']];
        try{$response=match($provider){'mpesa'=>$this->mpesa->initiate($request,$channel),'paystack'=>$this->paystack->initiate($request,$channel),'pesapal'=>$this->pesapal->initiate($request,$channel)};$this->repository->updateInitiated($tx,['merchant_request_id'=>(string)($response['order_tracking_id']??$response['provider_reference']??''),'checkout_request_id'=>(string)($response['checkout_request_id']??$response['provider_reference']??''),'result_code'=>'0','result_description'=>'Payment initialized','response_payload'=>json_encode($response,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);return ['transaction_id'=>$tx,'status'=>'pending','provider'=>$provider,'authorization_url'=>$response['authorization_url']??null,'message'=>$provider==='mpesa'?'Check the phone and approve the M-Pesa prompt.':'Continue on the secure payment page.','status_token'=>$raw];}catch(\Throwable $e){$this->repository->markFailed($tx,'INITIATION_ERROR',$e->getMessage());throw $e;}
    }

    public function status(int $id,string $token): array
    {
        $tx=$this->repository->publicTransaction($id,hash('sha256',$token));if(!$tx)throw new RuntimeException('Payment session not found.');
        return ['status'=>$tx['status'],'message'=>$tx['result_description'],'provider_reference'=>$tx['provider_reference']];
    }

    private function sign(array $payload): string{$raw=rtrim(strtr(base64_encode(json_encode($payload,JSON_UNESCAPED_SLASHES)), '+/', '-_'),'=');return $raw.'.'.$this->signature($raw);}
    private function signature(string $raw): string{$key=(string)Config::env('APP_KEY','');if($key==='')throw new RuntimeException('APP_KEY is required for payment links.');return hash_hmac('sha256',$raw,$key);}
}
