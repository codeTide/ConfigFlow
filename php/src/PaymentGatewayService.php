<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class PaymentGatewayService
{
    public function __construct(private SettingsRepository $settings)
    {
    }

    public function createTetrapayOrder(int $amount, string $orderRef): array
    {
        $apiKey = trim($this->settings->get('tetrapay_api_key', ''));
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'tetrapay_api_key_missing'];
        }

        $payload = [
            'api_key' => $apiKey,
            'amount' => $amount,
            'factorNumber' => $orderRef,
            'description' => 'ConfigFlow order ' . $orderRef,
        ];

        $response = $this->postJson(Config::tetrapayCreateUrl(), $payload);
        if (!($response['ok'] ?? false)) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = $response['data'] ?? [];
        $payUrl = (string) ($data['paymentUrl'] ?? $data['url'] ?? '');
        $authority = (string) ($data['authority'] ?? $data['token'] ?? '');

        if ($payUrl === '') {
            return ['ok' => false, 'error' => 'invalid_response'];
        }

        return ['ok' => true, 'pay_url' => $payUrl, 'authority' => $authority];
    }

    public function verifyTetrapay(string $authority): array
    {
        $apiKey = trim($this->settings->get('tetrapay_api_key', ''));
        if ($apiKey === '' || $authority === '') {
            return ['ok' => false, 'error' => 'missing_data'];
        }

        $response = $this->postJson(Config::tetrapayVerifyUrl(), [
            'api_key' => $apiKey,
            'authority' => $authority,
        ]);

        if (!($response['ok'] ?? false)) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = $response['data'] ?? [];
        $isPaid = (bool) ($data['paid'] ?? $data['success'] ?? false);

        return ['ok' => true, 'paid' => $isPaid, 'raw' => $data];
    }

    public function cryptoAddress(string $coin): string
    {
        return trim($this->settings->get('crypto_wallet_' . $coin, ''));
    }

    private function postJson(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $err !== '') {
            return ['ok' => false];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return ['ok' => false];
        }

        return ['ok' => true, 'data' => $decoded];
    }
}
