<?php

declare(strict_types=1);

namespace Modules\Finance\Providers;

use RuntimeException;

final class PaystackProvider implements PaymentProvider
{
    public function initiate(array $transaction, array $channel): array
    {
        $config = json_decode((string) ($channel['config_json'] ?? '{}'), true);
        if (!is_array($config) || trim((string) ($config['secret_key'] ?? '')) === '') {
            throw new RuntimeException('Paystack channel is missing secret_key.');
        }

        $email = trim((string) ($transaction['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid payer email is required for Paystack.');
        }

        $reference = 'EDU-' . (int) $transaction['id'] . '-' . bin2hex(random_bytes(5));
        $payload = [
            'email' => $email,
            'amount' => (string) ((int) round(((float) $transaction['amount']) * 100)),
            'currency' => 'KES',
            'reference' => $reference,
            'callback_url' => (string) $transaction['callback_url'],
            'metadata' => json_encode([
                'edusasa_transaction_id' => (int) $transaction['id'],
                'school_id' => (int) $transaction['school_id'],
                'student_id' => (int) $transaction['student_id'],
                'invoice_id' => $transaction['invoice_id'] ? (int) $transaction['invoice_id'] : null,
            ], JSON_UNESCAPED_SLASHES),
        ];

        $response = $this->request('POST', 'https://api.paystack.co/transaction/initialize', $payload, (string) $config['secret_key']);
        if (empty($response['status']) || empty($response['data']['authorization_url'])) {
            throw new RuntimeException((string) ($response['message'] ?? 'Paystack initialization failed.'));
        }

        return [
            'provider_reference' => $reference,
            'checkout_request_id' => $reference,
            'authorization_url' => (string) $response['data']['authorization_url'],
            'response' => $response,
        ];
    }

    public function verify(string $reference, array $channel): array
    {
        $config = json_decode((string) ($channel['config_json'] ?? '{}'), true);
        $secret = is_array($config) ? trim((string) ($config['secret_key'] ?? '')) : '';
        if ($secret === '') throw new RuntimeException('Paystack secret key is missing.');
        return $this->request('GET', 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference), [], $secret);
    }

    private function request(string $method, string $url, array $payload, string $secret): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secret, 'Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($payload !== []) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false) throw new RuntimeException('Paystack request failed: ' . $error);
        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) throw new RuntimeException('Paystack returned invalid JSON.');
        if ($status >= 400) throw new RuntimeException((string) ($decoded['message'] ?? 'Paystack request failed.'));
        return $decoded;
    }
}
