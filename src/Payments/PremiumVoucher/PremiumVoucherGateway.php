<?php

declare(strict_types=1);

namespace ConfigFlow\Bot\Payments\PremiumVoucher;

use ConfigFlow\Bot\PaymentMethodRepository;
use ConfigFlow\Bot\Support\AppLogger;
use ConfigFlow\Bot\Support\ErrorRef;

final class PremiumVoucherGateway
{
    public function __construct(
        private PaymentMethodRepository $paymentMethods,
        private ?AppLogger $logger = null,
    ) {
        $this->logger ??= new AppLogger();
    }

    /** @return array<string,mixed> */
    public function redeemVoucher(string $voucherCode, string $invoiceId): array
    {
        $config = $this->paymentMethods->getMethodConfig('premiumvoucher');
        $apiKey = trim((string) ($config['api_key'] ?? ''));
        $apiBaseUrl = rtrim(trim((string) ($config['api_base_url'] ?? '')), '/');
        $apiVersion = trim((string) ($config['api_version'] ?? ''));
        $redeemEndpoint = trim((string) ($config['redeem_endpoint'] ?? ''));

        if ($apiKey === '' || $apiBaseUrl === '' || $apiVersion === '' || $redeemEndpoint === '') {
            $errorRef = $this->logger->log('error', 'premiumvoucher', 'premiumvoucher_required_config_missing', 'Premium Voucher required config missing', [
                'gateway' => 'premiumvoucher',
                'stage' => 'config_validation',
            ], ErrorRef::make('PV'));

            return [
                'ok' => false,
                'redeemed' => false,
                'provider_status' => 'config_error',
                'effective_amount_usd' => 0,
                'raw' => [],
                'code' => 'premiumvoucher_required_config_missing',
                'message_key' => 'messages.user.payment.gateway.premiumvoucher_redeem_failed',
                'gateway_ref' => null,
                'error_ref' => $errorRef,
            ];
        }

        $url = $apiBaseUrl . '/api/' . rawurlencode($apiVersion) . '/' . ltrim($redeemEndpoint, '/');
        $payload = [
            'Voucher' => $voucherCode,
            'InvoiceId' => $invoiceId,
        ];

        $result = $this->postJson($url, $payload, [
            'Apikey: ' . $apiKey,
        ]);

        if (!(bool) ($result['transport_ok'] ?? false)) {
            return [
                'ok' => false,
                'redeemed' => false,
                'provider_status' => 'http_error',
                'effective_amount_usd' => 0,
                'raw' => $result,
                'code' => 'premiumvoucher_http_error',
                'message_key' => 'messages.user.payment.gateway.premiumvoucher_redeem_failed',
                'gateway_ref' => null,
            ];
        }

        $rawBody = trim((string) ($result['raw_body'] ?? ''));
        if ($rawBody === '') {
            return [
                'ok' => false,
                'redeemed' => false,
                'provider_status' => 'no_response',
                'effective_amount_usd' => 0,
                'raw' => $result,
                'code' => 'premiumvoucher_no_response',
                'message_key' => 'messages.user.payment.gateway.premiumvoucher_redeem_failed',
                'gateway_ref' => null,
            ];
        }

        if (!(bool) ($result['decoded_ok'] ?? false)) {
            return [
                'ok' => false,
                'redeemed' => false,
                'provider_status' => 'invalid_json',
                'effective_amount_usd' => 0,
                'raw' => $result,
                'code' => 'premiumvoucher_invalid_json',
                'message_key' => 'messages.user.payment.gateway.premiumvoucher_redeem_failed',
                'gateway_ref' => null,
            ];
        }

        $decoded = is_array($result['decoded_body'] ?? null) ? $result['decoded_body'] : [];
        $isSuccess = $this->isSuccessResponse($decoded);
        $effectiveAmount = $this->extractEffectiveUsdAmount($decoded);
        $gatewayRef = $this->extractGatewayReference($decoded, $invoiceId);

        if (!$isSuccess) {
            $status = $this->resolveFailedStatus($decoded);
            $code = $status === 'invalid_or_used' ? 'premiumvoucher_invalid_or_used' : 'premiumvoucher_redeem_failed';
            $messageKey = $status === 'invalid_or_used'
                ? 'messages.user.payment.gateway.premiumvoucher_invalid_or_used'
                : 'messages.user.payment.gateway.premiumvoucher_redeem_failed';

            return [
                'ok' => false,
                'redeemed' => false,
                'provider_status' => $status,
                'effective_amount_usd' => 0,
                'raw' => $decoded,
                'code' => $code,
                'message_key' => $messageKey,
                'gateway_ref' => $gatewayRef,
            ];
        }

        if ($effectiveAmount <= 0) {
            return [
                'ok' => false,
                'redeemed' => true,
                'provider_status' => 'zero_amount',
                'effective_amount_usd' => 0,
                'raw' => $decoded,
                'code' => 'premiumvoucher_zero_amount',
                'message_key' => 'messages.user.payment.gateway.premiumvoucher_redeem_failed',
                'gateway_ref' => $gatewayRef,
            ];
        }

        return [
            'ok' => true,
            'redeemed' => true,
            'provider_status' => 'success',
            'effective_amount_usd' => $effectiveAmount,
            'raw' => $decoded,
            'code' => null,
            'message_key' => null,
            'gateway_ref' => $gatewayRef,
        ];
    }

    /** @return array<string,mixed> */
    private function postJson(string $url, array $payload, array $headers = []): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['transport_ok' => false, 'http_status' => 0, 'curl_errno' => -1, 'curl_error' => 'curl_init_failed'];
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
            ], $headers),
            CURLOPT_POSTFIELDS => $json === false ? '{}' : $json,
        ]);

        $rawBody = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlError = curl_error($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $transportOk = $curlErrNo === 0 && $rawBody !== false;
        $rawString = is_string($rawBody) ? $rawBody : '';
        $decoded = json_decode($rawString, true);
        $decodedOk = is_array($decoded);

        return [
            'transport_ok' => $transportOk,
            'http_status' => $httpStatus,
            'curl_errno' => $curlErrNo,
            'curl_error' => $curlError,
            'raw_body' => $rawString,
            'decoded_ok' => $decodedOk,
            'decoded_body' => $decodedOk ? $decoded : null,
        ];
    }

    private function isSuccessResponse(array $data): bool
    {
        $statusValue = strtolower((string) ($data['status'] ?? $data['Status'] ?? $data['result'] ?? $data['Result'] ?? ''));
        if (in_array($statusValue, ['success', 'ok', 'redeemed', 'done', '1', 'true'], true)) {
            return true;
        }

        $successFlag = $data['success'] ?? $data['Success'] ?? null;
        if (is_bool($successFlag)) {
            return $successFlag;
        }

        if (is_numeric($successFlag)) {
            return ((int) $successFlag) === 1;
        }

        return false;
    }

    private function extractEffectiveUsdAmount(array $data): float
    {
        $candidates = [
            $data['effective_amount_usd'] ?? null,
            $data['effectiveAmountUsd'] ?? null,
            $data['amount_usd'] ?? null,
            $data['amountUsd'] ?? null,
            $data['amount'] ?? null,
            $data['Amount'] ?? null,
            $data['value'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return (float) $candidate;
            }
        }

        return 0.0;
    }

    private function extractGatewayReference(array $data, string $invoiceId): ?string
    {
        $ref = trim((string) ($data['reference'] ?? $data['ref'] ?? $data['transaction_id'] ?? $data['transactionId'] ?? $data['invoice_id'] ?? $data['InvoiceId'] ?? ''));
        if ($ref !== '') {
            return $ref;
        }

        return $invoiceId !== '' ? $invoiceId : null;
    }

    private function resolveFailedStatus(array $data): string
    {
        $haystack = strtolower(trim((string) (($data['message'] ?? '') . ' ' . ($data['error'] ?? '') . ' ' . ($data['status'] ?? '') . ' ' . ($data['Status'] ?? ''))));
        if ($haystack !== '' && (
            str_contains($haystack, 'invalid')
            || str_contains($haystack, 'not found')
            || str_contains($haystack, 'already')
            || str_contains($haystack, 'used')
            || str_contains($haystack, 'redeemed')
        )) {
            return 'invalid_or_used';
        }

        return 'redeem_failed';
    }
}
