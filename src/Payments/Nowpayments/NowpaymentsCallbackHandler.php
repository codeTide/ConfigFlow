<?php

declare(strict_types=1);

namespace ConfigFlow\Bot\Payments\Nowpayments;

use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\PaymentMethodRepository;
use ConfigFlow\Bot\Support\AppLogger;
use ConfigFlow\Bot\Support\ErrorRef;

final class NowpaymentsCallbackHandler
{
    public function __construct(
        private Database $database,
        private PaymentMethodRepository $methods,
        private ?AppLogger $logger = null
    ) {
        $this->logger ??= new AppLogger();
    }

    /** @param array<string,mixed> $payload */
    public function handle(array $payload): array
    {
        $traceRef = ErrorRef::make('CB');
        $this->logger->log('info', 'callback', 'nowpayments_callback_received', 'NOWPayments callback received', [
            'gateway' => 'nowpayments',
            'stage' => 'callback_received',
            'request_payload' => $payload,
        ], $traceRef);

        $orderId = trim((string) ($payload['order_id'] ?? ''));
        if (!preg_match('/^cf-pay:(\d+)$/', $orderId, $m)) {
            $ref = $this->logger->log('warning', 'callback', 'nowpayments_invalid_order_id', 'NOWPayments callback invalid order_id', [
                'gateway' => 'nowpayments',
                'stage' => 'callback_resolve_payment',
                'request_payload' => ['order_id' => $orderId],
            ], ErrorRef::make('CB'));
            return ['ok' => false, 'error' => 'invalid_order_id', 'error_ref' => $ref];
        }

        $paymentId = (int) $m[1];
        $payment = $this->database->getPaymentById($paymentId);
        if (!is_array($payment) || (string) ($payment['payment_method'] ?? '') !== 'nowpayments') {
            $ref = $this->logger->log('warning', 'callback', 'nowpayments_payment_not_found', 'NOWPayments callback payment not found', [
                'gateway' => 'nowpayments',
                'stage' => 'callback_resolve_payment',
                'payment_id' => $paymentId,
            ], ErrorRef::make('CB'));
            return ['ok' => false, 'error' => 'payment_not_found', 'error_ref' => $ref];
        }

        $status = strtolower(trim((string) ($payload['payment_status'] ?? '')));
        $providerSnapshot = [
            'gateway' => 'nowpayments',
            'order_id' => $orderId,
            'invoice_id' => (string) ($payload['invoice_id'] ?? (($payload['id'] ?? ''))),
            'provider_payment_id' => (string) ($payload['payment_id'] ?? ''),
            'last_provider_status' => $status,
            'last_ipn_at' => gmdate('c'),
        ];
        $this->database->setPaymentProviderPayload($paymentId, $this->mergeProviderPayload($paymentId, $providerSnapshot));

        $kind = (string) ($payment['kind'] ?? '');
        $changed = false;
        if (in_array($status, ['finished'], true)) {
            $changed = $kind === 'wallet_topup'
                ? $this->database->markWalletTopupPaidIfWaitingGateway($paymentId)
                : $this->database->markPaymentAndPendingPaidIfWaitingGateway($paymentId);
            $this->logger->log($changed ? 'info' : 'warning', 'callback', $changed ? 'nowpayments_finalized' : 'nowpayments_duplicate_noop', 'NOWPayments callback finalize result', [
                'gateway' => 'nowpayments',
                'stage' => 'callback_finalize',
                'payment_id' => $paymentId,
                'provider_status' => $status,
            ], $traceRef);
            return ['ok' => true, 'paid' => true, 'changed' => $changed, 'status' => $status, 'payment_id' => $paymentId];
        }

        if (in_array($status, ['failed', 'expired', 'refunded'], true)) {
            $changed = $this->database->markPaymentFailedIfWaitingGateway($paymentId, 'nowpayments_' . $status);
            $this->logger->log($changed ? 'info' : 'warning', 'callback', 'nowpayments_terminal_negative', 'NOWPayments callback terminal negative status', [
                'gateway' => 'nowpayments',
                'stage' => 'callback_finalize',
                'payment_id' => $paymentId,
                'provider_status' => $status,
            ], $traceRef);
            return ['ok' => true, 'paid' => false, 'changed' => $changed, 'status' => $status, 'payment_id' => $paymentId];
        }

        return ['ok' => true, 'paid' => false, 'changed' => false, 'status' => $status, 'payment_id' => $paymentId];
    }

    public function verifySignature(string $rawBody, string $signature): bool
    {
        $signature = strtolower(trim($signature));
        if ($signature === '') {
            return false;
        }
        $config = $this->methods->getMethodConfig('nowpayments');
        $secret = trim((string) ($config['ipn_secret'] ?? ''));
        if ($secret === '') {
            return false;
        }
        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            return false;
        }
        $sorted = $this->recursiveKsort($decoded);
        $normalized = json_encode($sorted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($normalized)) {
            return false;
        }
        $computed = hash_hmac('sha512', $normalized, $secret);
        return hash_equals($computed, $signature);
    }

    /** @param array<string,mixed> $data
     *  @return array<string,mixed>
     */
    private function recursiveKsort(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveKsort($value);
            }
        }
        ksort($data);
        return $data;
    }

    /** @param array<string,mixed> $delta
     *  @return array<string,mixed>
     */
    private function mergeProviderPayload(int $paymentId, array $delta): array
    {
        $existing = $this->database->getPaymentById($paymentId);
        $base = [];
        if (is_array($existing) && is_string($existing['provider_payload'] ?? null)) {
            $decoded = json_decode((string) $existing['provider_payload'], true);
            if (is_array($decoded)) {
                $base = $decoded;
            }
        }
        return array_merge($base, $delta);
    }
}
