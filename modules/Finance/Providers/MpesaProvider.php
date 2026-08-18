<?php

declare(strict_types=1);

namespace Modules\Finance\Providers;

use DateTimeImmutable;
use RuntimeException;

final class MpesaProvider implements PaymentProvider
{
    public function __construct(private readonly DarajaClient $client) {}

    public function initiate(array $transaction,array $channel): array
    {
        $config=json_decode((string)($channel['config_json']??'{}'),true);
        if(!is_array($config)) throw new RuntimeException('M-Pesa channel configuration is invalid.');
        foreach(['consumer_key','consumer_secret','shortcode','passkey'] as $key){if(trim((string)($config[$key]??''))==='')throw new RuntimeException('M-Pesa channel is missing '.$key.'.');}
        $environment=strtolower((string)($config['environment']??'sandbox'));
        if(!in_array($environment,['sandbox','live'],true))throw new RuntimeException('Invalid M-Pesa environment.');
        $phone=$this->phone((string)$transaction['phone']);
        $timestamp=(new DateTimeImmutable('now'))->format('YmdHis');
        $shortcode=(string)$config['shortcode'];
        $payload=[
            'BusinessShortCode'=>(int)$shortcode,
            'Password'=>base64_encode($shortcode.(string)$config['passkey'].$timestamp),
            'Timestamp'=>$timestamp,
            'TransactionType'=>strtolower((string)($channel['type']??'mpesa'))==='mpesa'&&strtolower((string)($config['account_type']??'paybill'))==='till'?'CustomerBuyGoodsOnline':'CustomerPayBillOnline',
            'Amount'=>(int)round((float)$transaction['amount']),
            'PartyA'=>(int)$phone,
            'PartyB'=>(int)$shortcode,
            'PhoneNumber'=>(int)$phone,
            'CallBackURL'=>(string)$transaction['callback_url'],
            'AccountReference'=>substr((string)($transaction['account_reference']??'EduSasa'),0,80),
            'TransactionDesc'=>substr('School fees payment',0,13),
        ];
        $token=$this->client->accessToken((string)$config['consumer_key'],(string)$config['consumer_secret'],$environment);
        $response=$this->client->stkPush($token,$payload,$environment);
        $response['environment']=$environment;
        return $response;
    }

    private function phone(string $phone): string
    {
        $phone=preg_replace('/\D+/','',$phone)??'';
        if(str_starts_with($phone,'0'))$phone='254'.substr($phone,1);
        elseif(str_starts_with($phone,'7')||str_starts_with($phone,'1'))$phone='254'.$phone;
        if(!preg_match('/^254[17]\d{8}$/',$phone))throw new RuntimeException('Enter a valid Kenyan M-Pesa phone number.');
        return $phone;
    }
}
