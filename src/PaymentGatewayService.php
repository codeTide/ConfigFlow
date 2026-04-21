<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

use ConfigFlow\Bot\Support\AppError;
use ConfigFlow\Bot\Support\AppLogger;
use ConfigFlow\Bot\Support\ErrorRef;
use ConfigFlow\Bot\Support\PayloadSanitizer;

final class PaymentGatewayService
{
    private const TETRAPAY_CREATE_URL = 'https://tetra98.com/api/create_order';
    private const TETRAPAY_VERIFY_URL = 'https://tetra98.com/api/verify';
    private const NOWPAYMENTS_CREATE_URL = 'https://api.nowpayments.io/v1/invoice';

    public function __construct(
        private SettingsRepository $settings,
        private ?PaymentMethodRepository $paymentMethods = null,
        private ?AppLogger $logger = null
    )
    {
        $this->logger ??= new AppLogger();
    }

    public function createTetrapayOrder(
        int $amount,
        string $hashSeed,
        string $description,
        string $callbackUrl,
        array $customer = []
    ): array
    {
        $config = $this->paymentMethods?->getMethodConfig('tetrapay') ?? [];
        $apiKey = trim((string) ($config['api_key'] ?? $this->settings->get('tetrapay_api_key', '')));
        if ($apiKey === '') {
            $errorRef = $this->logger->log('error', 'tetrapay', 'tetrapay_api_key_missing', 'Tetrapay api key missing', [
                'gateway' => 'tetrapay',
                'stage' => 'config_validation',
            ], ErrorRef::make('TP'));
            return AppError::make('tetrapay_api_key_missing', 'messages.user.payment.gateway.tetrapay_invoice_error', 'tetrapay', 'config_validation', [], false, $errorRef);
        }

        if (trim($callbackUrl) === '') {
            $errorRef = $this->logger->log('error', 'tetrapay', 'tetrapay_callback_missing', 'Tetrapay callback missing', [
                'gateway' => 'tetrapay',
                'stage' => 'config_validation',
            ], ErrorRef::make('TP'));
            return AppError::make('tetrapay_callback_missing', 'messages.user.payment.gateway.tetrapay_invoice_error', 'tetrapay', 'config_validation', [], false, $errorRef);
        }
        $hashId = substr(hash('sha256', $hashSeed), 0, 40);
        $payload = [
            'ApiKey' => $apiKey,
            'Hash_id' => $hashId,
            'Amount' => $amount,
            'Description' => $description,
            'CallbackURL' => $callbackUrl,
        ];
        $email = trim((string) ($customer['email'] ?? ''));
        if ($email !== '') {
            $payload['Email'] = $email;
        }
        $mobile = trim((string) ($customer['mobile'] ?? ''));
        if ($mobile !== '') {
            $payload['Mobile'] = $mobile;
        }

        $createUrl = self::TETRAPAY_CREATE_URL;
        $response = $this->postJson($createUrl, $payload);
        if (!(bool) ($response['transport_ok'] ?? false)) {
            $isTimeout = ((int) ($response['curl_errno'] ?? 0)) === 28;
            $code = $isTimeout ? 'tetrapay_timeout' : 'tetrapay_transport_error';
            $errorRef = $this->logger->log('error', 'tetrapay', $code, 'Tetrapay create order transport failure', [
                'gateway' => 'tetrapay',
                'stage' => 'create_order',
                'http_status' => (int) ($response['http_status'] ?? 0),
                'provider_error' => (string) ($response['curl_error'] ?? ''),
                'request_payload' => PayloadSanitizer::sanitize($payload),
                'raw_response' => (string) ($response['raw_body'] ?? ''),
            ], ErrorRef::make('TP'));
            return AppError::make($code, 'messages.user.payment.gateway.tetrapay_invoice_error', 'tetrapay', 'create_order', [
                'http_status' => (int) ($response['http_status'] ?? 0),
                'curl_errno' => (int) ($response['curl_errno'] ?? 0),
                'curl_error' => (string) ($response['curl_error'] ?? ''),
            ], true, $errorRef);
        }
        if (!(bool) ($response['decoded_ok'] ?? false)) {
            $errorRef = $this->logger->log('error', 'tetrapay', 'tetrapay_invalid_json', 'Tetrapay create order invalid JSON', [
                'gateway' => 'tetrapay',
                'stage' => 'create_order',
                'http_status' => (int) ($response['http_status'] ?? 0),
                'provider_error' => (string) ($response['decode_error'] ?? ''),
                'request_payload' => PayloadSanitizer::sanitize($payload),
                'raw_response' => (string) ($response['raw_body'] ?? ''),
            ], ErrorRef::make('TP'));
            return AppError::make('tetrapay_invalid_json', 'messages.user.payment.gateway.tetrapay_invoice_error', 'tetrapay', 'create_order', [
                'http_status' => (int) ($response['http_status'] ?? 0),
                'decode_error' => (string) ($response['decode_error'] ?? ''),
            ], false, $errorRef);
        }
        if ((int) ($response['http_status'] ?? 0) < 200 || (int) ($response['http_status'] ?? 0) >= 300) {
            $errorRef = $this->logger->log('error', 'tetrapay', 'tetrapay_http_error', 'Tetrapay create order HTTP error', [
                'gateway' => 'tetrapay',
                'stage' => 'create_order',
                'http_status' => (int) ($response['http_status'] ?? 0),
                'request_payload' => PayloadSanitizer::sanitize($payload),
                'response_payload' => (array) ($response['decoded_body'] ?? []),
                'raw_response' => (string) ($response['raw_body'] ?? ''),
            ], ErrorRef::make('TP'));
            return AppError::make('tetrapay_http_error', 'messages.user.payment.gateway.tetrapay_invoice_error', 'tetrapay', 'create_order', [
                'http_status' => (int) ($response['http_status'] ?? 0),
            ], true, $errorRef);
        }

        $data = is_array($response['decoded_body'] ?? null) ? $response['decoded_body'] : [];
        $providerStatus = (int) ($data['status'] ?? 0);
        if ($providerStatus !== 100 && $providerStatus !== 1 && $providerStatus !== 0) {
            $errorRef = $this->logger->log('warning', 'tetrapay', 'tetrapay_provider_rejected', 'Tetrapay provider rejected create order', [
                'gateway' => 'tetrapay',
                'stage' => 'create_order',
                'http_status' => (int) ($response['http_status'] ?? 0),
                'response_payload' => $data,
                'provider_error' => (string) ($data['message'] ?? ''),
            ], ErrorRef::make('TP'));
            return AppError::make('tetrapay_provider_rejected', 'messages.user.payment.gateway.tetrapay_invoice_error', 'tetrapay', 'create_order', [
                'http_status' => (int) ($response['http_status'] ?? 0),
                'provider_status' => $providerStatus,
                'provider_message' => (string) ($data['message'] ?? ''),
            ], false, $errorRef);
        }
        $payUrl = (string) ($data['payment_url_web'] ?? $data['payment_url_bot'] ?? '');
        $authority = (string) ($data['Authority'] ?? '');
        $trackingId = (string) ($data['tracking_id'] ?? '');

        if ($payUrl === '') {
            $errorRef = $this->logger->log('error', 'tetrapay', 'tetrapay_missing_pay_url', 'Tetrapay create order missing pay url', [
                'gateway' => 'tetrapay',
                'stage' => 'create_order',
                'response_payload' => $data,
            ], ErrorRef::make('TP'));
            return AppError::make('tetrapay_missing_pay_url', 'messages.user.payment.gateway.tetrapay_invoice_error', 'tetrapay', 'create_order', [
                'provider_status' => $providerStatus,
                'response' => $data,
            ], false, $errorRef);
        }

        return ['ok' => true, 'pay_url' => $payUrl, 'authority' => $authority, 'tracking_id' => $trackingId, 'hash_id' => $hashId, 'raw' => $data];
    }

    public function verifyTetrapay(string $authority, string $hashId = ''): array
    {
        $config = $this->paymentMethods?->getMethodConfig('tetrapay') ?? [];
        $apiKey = trim((string) ($config['api_key'] ?? $this->settings->get('tetrapay_api_key', '')));
        if ($apiKey === '' || $authority === '') {
            return ['ok' => false, 'error' => 'missing_data'];
        }

        $payload = [
            'ApiKey' => $apiKey,
            'Authority' => $authority,
        ];
        if ($hashId !== '') {
            $payload['Hash_id'] = $hashId;
        }
        $response = $this->postJson(self::TETRAPAY_VERIFY_URL, $payload);
        if (!(bool) ($response['transport_ok'] ?? false)) {
            $isTimeout = ((int) ($response['curl_errno'] ?? 0)) === 28;
            $errCode = $isTimeout ? 'tetrapay_timeout' : 'tetrapay_transport_error';
            $errorRef = $this->logger->log('error', 'tetrapay', 'tetrapay_transport_error', 'Tetrapay verify transport failure', [
                'gateway' => 'tetrapay',
                'stage' => 'verify',
                'http_status' => (int) ($response['http_status'] ?? 0),
                'provider_error' => (string) ($response['curl_error'] ?? ''),
                'request_payload' => PayloadSanitizer::sanitize($payload),
                'raw_response' => (string) ($response['raw_body'] ?? ''),
            ], ErrorRef::make('TP'));
            return AppError::make($errCode, 'messages.user.payment.not_confirmed', 'tetrapay', 'verify', [
                'http_status' => (int) ($response['http_status'] ?? 0),
                'curl_error' => (string) ($response['curl_error'] ?? ''),
            ], true, $errorRef);
        }
        if (!(bool) ($response['decoded_ok'] ?? false)) {
            $errorRef = $this->logger->log('error', 'tetrapay', 'tetrapay_invalid_json', 'Tetrapay verify invalid JSON', [
                'gateway' => 'tetrapay',
                'stage' => 'verify',
                'raw_response' => (string) ($response['raw_body'] ?? ''),
            ], ErrorRef::make('TP'));
            return AppError::make('tetrapay_invalid_json', 'messages.user.payment.not_confirmed', 'tetrapay', 'verify', [], false, $errorRef);
        }
        $data = is_array($response['decoded_body'] ?? null) ? $response['decoded_body'] : [];
        if (!array_key_exists('status', $data)) {
            $errorRef = $this->logger->log('error', 'tetrapay', 'tetrapay_invalid_response', 'Tetrapay verify missing status', [
                'gateway' => 'tetrapay',
                'stage' => 'verify',
                'response_payload' => $data,
            ], ErrorRef::make('TP'));
            return AppError::make('tetrapay_invalid_response', 'messages.user.payment.not_confirmed', 'tetrapay', 'verify', [], false, $errorRef);
        }
        $statusCode = (int) ($data['status'] ?? 0);
        $returnedAuthority = trim((string) ($data['Authority'] ?? ''));
        if ($returnedAuthority !== '' && $returnedAuthority !== $authority) {
            $errorRef = $this->logger->log('warning', 'tetrapay', 'tetrapay_authority_mismatch', 'Tetrapay verify authority mismatch', [
                'gateway' => 'tetrapay',
                'stage' => 'verify',
                'response_payload' => $data,
                'provider_error' => 'authority_mismatch',
            ], ErrorRef::make('TP'));
            return AppError::make('tetrapay_authority_mismatch', 'messages.user.payment.not_confirmed', 'tetrapay', 'verify', [], false, $errorRef);
        }
        $isPaid = $statusCode === 100;

        if (!$isPaid) {
            $this->logger->log('info', 'tetrapay', 'tetrapay_status_not_paid', 'Tetrapay verify status is not paid', [
                'gateway' => 'tetrapay',
                'stage' => 'verify',
                'response_payload' => $data,
                'provider_error' => (string) $statusCode,
            ], ErrorRef::make('TP'));
        }

        return ['ok' => true, 'paid' => $isPaid, 'status' => $statusCode, 'code' => $isPaid ? 'tetrapay_paid' : 'tetrapay_status_not_paid', 'raw' => $data];
    }



    public function createNowpaymentsInvoice(int $amount, string $orderId, string $description, array $options = []): array
    {
        $config = $this->paymentMethods?->getMethodConfig('nowpayments') ?? [];
        $apiKey = trim((string) ($config['api_key'] ?? ''));
        $ipnSecret = trim((string) ($config['ipn_secret'] ?? ''));
        $callbackUrl = trim((string) ($config['callback_url'] ?? ''));

        if ($apiKey === '' || $ipnSecret === '' || $callbackUrl === '') {
            $errorRef = $this->logger->log('error', 'nowpayments', 'nowpayments_required_config_missing', 'NOWPayments required config missing', [
                'gateway' => 'nowpayments',
                'stage' => 'config_validation',
                'request_payload' => [
                    'has_api_key' => $apiKey !== '',
                    'has_ipn_secret' => $ipnSecret !== '',
                    'has_callback_url' => $callbackUrl !== '',
                ],
            ], ErrorRef::make('NP'));
            return AppError::make('nowpayments_required_config_missing', 'messages.user.payment.gateway.nowpayments_invoice_error', 'nowpayments', 'config_validation', [], false, $errorRef);
        }
        $payload = [
            'price_amount' => $amount,
            'price_currency' => 'usd',
            'order_id' => $orderId,
            'order_description' => $description,
            'ipn_callback_url' => $callbackUrl,
            'is_fixed_rate' => ((int) ($config['is_fixed_rate'] ?? 0)) === 1,
            'is_fee_paid_by_user' => ((int) ($config['is_fee_paid_by_user'] ?? 0)) === 1,
        ];

        $response = $this->postJsonHeaders(self::NOWPAYMENTS_CREATE_URL, $payload, [
            'x-api-key: ' . $apiKey,
        ]);

        if (!(bool) ($response['transport_ok'] ?? false)) {
            $isTimeout = ((int) ($response['curl_errno'] ?? 0)) === 28;
            $code = $isTimeout ? 'nowpayments_timeout' : 'nowpayments_transport_error';
            $errorRef = $this->logger->log('error', 'nowpayments', $code, 'NOWPayments create invoice transport failure', [
                'gateway' => 'nowpayments',
                'stage' => 'create_invoice',
                'http_status' => (int) ($response['http_status'] ?? 0),
                'provider_error' => (string) ($response['curl_error'] ?? ''),
                'request_payload' => PayloadSanitizer::sanitize($payload),
                'raw_response' => (string) ($response['raw_body'] ?? ''),
            ], ErrorRef::make('NP'));
            return AppError::make($code, 'messages.user.payment.gateway.nowpayments_invoice_error', 'nowpayments', 'create_invoice', [
                'http_status' => (int) ($response['http_status'] ?? 0),
                'curl_errno' => (int) ($response['curl_errno'] ?? 0),
            ], true, $errorRef);
        }
        if (!(bool) ($response['decoded_ok'] ?? false)) {
            $errorRef = $this->logger->log('error', 'nowpayments', 'nowpayments_invalid_json', 'NOWPayments create invoice invalid JSON', [
                'gateway' => 'nowpayments',
                'stage' => 'create_invoice',
                'provider_error' => (string) ($response['decode_error'] ?? ''),
                'request_payload' => PayloadSanitizer::sanitize($payload),
                'raw_response' => (string) ($response['raw_body'] ?? ''),
            ], ErrorRef::make('NP'));
            return AppError::make('nowpayments_invalid_json', 'messages.user.payment.gateway.nowpayments_invoice_error', 'nowpayments', 'create_invoice', [], false, $errorRef);
        }
        if ((int) ($response['http_status'] ?? 0) < 200 || (int) ($response['http_status'] ?? 0) >= 300) {
            $errorRef = $this->logger->log('error', 'nowpayments', 'nowpayments_http_error', 'NOWPayments create invoice HTTP error', [
                'gateway' => 'nowpayments',
                'stage' => 'create_invoice',
                'http_status' => (int) ($response['http_status'] ?? 0),
                'request_payload' => PayloadSanitizer::sanitize($payload),
                'response_payload' => (array) ($response['decoded_body'] ?? []),
            ], ErrorRef::make('NP'));
            return AppError::make('nowpayments_http_error', 'messages.user.payment.gateway.nowpayments_invoice_error', 'nowpayments', 'create_invoice', [
                'http_status' => (int) ($response['http_status'] ?? 0),
            ], true, $errorRef);
        }

        $data = is_array($response['decoded_body'] ?? null) ? $response['decoded_body'] : [];
        $invoiceId = (string) ($data['id'] ?? '');
        $payUrl = trim((string) ($data['invoice_url'] ?? ''));
        if ($invoiceId === '' || $payUrl === '') {
            $errorRef = $this->logger->log('error', 'nowpayments', 'nowpayments_invalid_response', 'NOWPayments create invoice missing id/url', [
                'gateway' => 'nowpayments',
                'stage' => 'create_invoice',
                'response_payload' => $data,
            ], ErrorRef::make('NP'));
            return AppError::make('nowpayments_invalid_response', 'messages.user.payment.gateway.nowpayments_invoice_error', 'nowpayments', 'create_invoice', [], false, $errorRef);
        }

        return [
            'ok' => true,
            'pay_url' => $payUrl,
            'invoice_id' => $invoiceId,
            'order_id' => $orderId,
            'raw' => $data,
        ];
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
        $errno = curl_errno($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return [
            'ok' => ($raw !== false && $err === ''),
            'transport_ok' => ($raw !== false && $err === ''),
            'provider_ok' => $httpStatus >= 200 && $httpStatus < 300,
            'http_status' => $httpStatus,
            'curl_error' => $err,
            'curl_errno' => $errno,
            'raw_body' => is_string($raw) ? $raw : '',
            'decoded_ok' => is_array($decoded),
            'decoded_body' => is_array($decoded) ? $decoded : null,
            'decode_error' => is_array($decoded) ? '' : json_last_error_msg(),
        ];
    }

    private function postJsonHeaders(string $url, array $payload, array $headers): array
    {
        $allHeaders = array_merge(['Content-Type: application/json; charset=utf-8'], $headers);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $errno = curl_errno($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return [
            'ok' => ($raw !== false && $err === '' && is_array($decoded)),
            'transport_ok' => ($raw !== false && $err === ''),
            'provider_ok' => $httpStatus >= 200 && $httpStatus < 300,
            'http_status' => $httpStatus,
            'curl_error' => $err,
            'curl_errno' => $errno,
            'raw_body' => is_string($raw) ? $raw : '',
            'decoded_ok' => is_array($decoded),
            'decoded_body' => is_array($decoded) ? $decoded : null,
            'decode_error' => is_array($decoded) ? '' : json_last_error_msg(),
            'data' => is_array($decoded) ? $decoded : [],
        ];
    }

}
