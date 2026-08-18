<?php

declare(strict_types=1);

namespace Modules\Finance\Providers;

use RuntimeException;

final class PesapalProvider implements PaymentProvider
{
    public function initiate(array $transaction, array $channel): array
    {
        $config = json_decode((string) ($channel['config_json'] ?? '{}'), true);
        if (!is_array($config)) throw new RuntimeException('Pesapal channel configuration is invalid.');
        $key = trim((string) ($config['consumer_key'] ?? ''));
        $secret = trim((string) ($config['consumer_secret'] ?? ''));
        $ipn = trim((string) ($config['notification_id'] ?? ''));
        if ($key === '' || $secret === '' || $ipn === '') throw new RuntimeException('Pesapal requires consumer_key, consumer_secret and notification_id.');
        $env = strtolower((string) ($config['environment'] ?? 'sandbox')) === 'live' ? 'live' : 'sandbox';
        $base = $env === 'live' ? 'https://pay.pesapal.com/v3' : 'https://cybqa.pesapal.com/pesapalv3';
        $token = $this->token($base, $key, $secret);
        $reference = 'EDU-' . (int) $transaction['id'] . '-' . bin2hex(random_bytes(5));
        $payload = [
            'id' => $reference,
            'currency' => 'KES',
            'amount' => round((float) $transaction['amount'], 2),
            'description' => 'EduSasa school fee payment',
            'callback_url' => (string) $transaction['callback_url'],
            'cancellation_url' => (string) $transaction['callback_url'] . '&cancelled=1',
            'notification_id' => $ipn,
            'billing_address' => [
                'email_address' => (string) ($transaction['email'] ?? ''),
                'phone_number' => (string) ($transaction['phone'] ?? ''),
                'country_code' => 'KE',
                'first_name' => (string) ($transaction['first_name'] ?? ''),
                'last_name' => (string) ($transaction['last_name'] ?? ''),
            ],
        ];
        $response = $this->request('POST', $base . '/api/Transactions/SubmitOrderRequest', $payload, $token);
        $url = (string) ($response['redirect_url'] ?? '');
        if ($url === '') throw new RuntimeException((string) ($response['error']['message'] ?? 'Pesapal did not return a payment URL.'));
        return [
            'provider_reference' => $reference,
            'checkout_request_id' => $reference,
            'authorization_url' => $url,
            'order_tracking_id' => (string) ($response['order_tracking_id'] ?? ''),
            'response' => $response,
        ];
    }

    public function status(string $trackingId, array $channel): array
    {
        $config = json_decode((string) ($channel['config_json'] ?? '{}'), true);
        if (!is_array($config)) throw new RuntimeException('Pesapal channel configuration is invalid.');
        $key = trim((string) ($config['consumer_key'] ?? ''));
        $secret = trim((string) ($config['consumer_secret'] ?? ''));
        $env = strtolower((string) ($config['environment'] ?? 'sandbox')) === 'live' ? 'live' : 'sandbox';
        $base = $env === 'live' ? 'https://pay.pesapal.com/v3' : 'https://cybqa.pesapal.com/pesapalv3';
        return $this->request('GET', $base . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . rawurlencode($trackingId), [], $this->token($base, $key, $secret));
    }

    private function token(string $base, string $key, string $secret): string
    {
        $response = $this->request('POST', $base . '/api/Auth/RequestToken', ['consumer_key' => $key, 'consumer_secret' => $secret], null);
        $token = (string) ($response['token'] ?? '');
        if ($token === '') throw new RuntimeException('Pesapal authentication failed.');
        return $token;
    }

    private function request(string $method, string $url, array $payload, ?string $token): array
    {
        $ch = curl_init($url);
        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        if ($token !== null) $headers[] = 'Authorization: Bearer ' . $token;
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
        if ($payload !== []) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
        $body = curl_exec($ch); $error = curl_error($ch); $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($body === false) throw new RuntimeException('Pesapal request failed: ' . $error);
        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) throw new RuntimeException('Pesapal returned invalid JSON.');
        if ($status >= 400) throw new RuntimeException((string) ($decoded['error']['message'] ?? 'Pesapal request failed.'));
        return $decoded;
    }
}
