<?php

declare(strict_types=1);

namespace Modules\Finance\Providers;

use RuntimeException;

final class DarajaClient
{
    public function __construct() {}

    public function accessToken(string $consumerKey, string $consumerSecret, string $environment): string
    {
        $base=$this->baseUrl($environment);
        $result=$this->request('GET',$base.'/oauth/v1/generate?grant_type=client_credentials',[
            'Authorization'=>'Basic '.base64_encode($consumerKey.':'.$consumerSecret),
            'Accept'=>'application/json',
        ]);
        $token=(string)($result['body']['access_token']??'');
        if($token==='') throw new RuntimeException('M-Pesa authentication failed.');
        return $token;
    }

    public function stkPush(string $token,array $payload,string $environment): array
    {
        $result=$this->request('POST',$this->baseUrl($environment).'/mpesa/stkpush/v1/processrequest',[
            'Authorization'=>'Bearer '.$token,
            'Content-Type'=>'application/json',
            'Accept'=>'application/json',
        ],$payload);
        return $result['body'];
    }

    private function baseUrl(string $environment): string
    {
        return strtolower($environment)==='live'?'https://api.safaricom.co.ke':'https://sandbox.safaricom.co.ke';
    }

    private function request(string $method,string $url,array $headers=[],?array $payload=null): array
    {
        if(!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension is required for M-Pesa payments.');
        $ch=curl_init($url);
        if($ch===false) throw new RuntimeException('Unable to initialize M-Pesa connection.');
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$this->headers($headers),CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
        if($payload!==null) curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload,JSON_UNESCAPED_SLASHES));
        $raw=curl_exec($ch);$error=curl_error($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if($raw===false||$error!=='') throw new RuntimeException('Unable to reach M-Pesa provider.');
        $body=json_decode((string)$raw,true);
        if(!is_array($body)) throw new RuntimeException('M-Pesa returned an invalid response.');
        if($status<200||$status>=300) throw new RuntimeException((string)($body['errorMessage']??$body['errorCode']??'M-Pesa request failed.'));
        return ['status'=>$status,'body'=>$body];
    }

    private function headers(array $headers): array
    {
        $out=[];foreach($headers as $name=>$value)$out[]=$name.': '.$value;return $out;
    }
}
