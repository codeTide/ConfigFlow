<?php

declare(strict_types=1);

namespace ConfigFlow\Bot\Payments\Tetrapay;

use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\Support\AppLogger;
use ConfigFlow\Bot\Support\ErrorRef;

final class TetrapayCallbackHandler
{
    public function __construct(
        private Database $database,
        private TetrapayGateway $gateway,
        private ?AppLogger $logger = null
    ) {
        $this->logger ??= new AppLogger();
    }

    /** @param array<string,mixed> $payload */
    public function handle(array $payload): array
    {
        $traceRef = ErrorRef::make('CB');
        $this->logger->log('info', 'callback', 'tetrapay_callback_received', 'Tetrapay callback received', [
            'gateway' => 'tetrapay',
            'stage' => 'callback_received',
            'request_payload' => $payload,
        ], $traceRef);

        $authority = trim((string) ($payload['Authority'] ?? $payload['authority'] ?? ''));
        if ($authority === '') {
            $ref = $this->logger->log('warning', 'callback', 'missing_authority', 'Tetrapay callback missing authority', [
                'gateway' => 'tetrapay',
                'stage' => 'callback_received',
                'request_payload' => $payload,
            ], ErrorRef::make('CB'));
            return ['ok' => false, 'error' => 'missing_authority', 'error_ref' => $ref];
        }

        $payment = $this->database->getPaymentByGatewayRef($authority, 'tetrapay');
        if (!is_array($payment)) {
            $ref = $this->logger->log('warning', 'callback', 'payment_not_found', 'Tetrapay callback payment not found', [
                'gateway' => 'tetrapay',
                'stage' => 'callback_resolve_payment',
                'provider_error' => 'payment_not_found',
                'request_payload' => $payload,
            ], ErrorRef::make('CB'));
            return ['ok' => false, 'error' => 'payment_not_found', 'error_ref' => $ref];
        }

        $providerPayload = json_decode((string) ($payment['provider_payload'] ?? ''), true);
        $hashId = is_array($providerPayload) ? trim((string) ($providerPayload['hash_id'] ?? '')) : '';
        $verify = $this->gateway->verifyByAuthority($authority, $hashId);
        if (!(bool) ($verify['ok'] ?? false)) {
            $ref = $this->logger->log('error', 'callback', 'verify_failed', 'Tetrapay callback verify failed', [
                'gateway' => 'tetrapay',
                'stage' => 'callback_verify',
                'payment_id' => (int) ($payment['id'] ?? 0),
                'response_payload' => $verify,
                'provider_error' => (string) ($verify['code'] ?? $verify['error'] ?? 'verify_failed'),
            ], ErrorRef::make('CB'));
            return ['ok' => false, 'error' => (string) ($verify['error'] ?? 'verify_failed'), 'error_ref' => $ref];
        }
        if (!(bool) ($verify['paid'] ?? false)) {
            $this->logger->log('info', 'callback', 'verify_not_paid', 'Tetrapay callback verify indicates unpaid', [
                'gateway' => 'tetrapay',
                'stage' => 'callback_verify',
                'payment_id' => (int) ($payment['id'] ?? 0),
                'response_payload' => $verify,
            ], $traceRef);
            return ['ok' => true, 'paid' => false, 'status' => (int) ($verify['status'] ?? 0), 'payment_id' => (int) ($payment['id'] ?? 0)];
        }

        $kind = (string) ($payment['kind'] ?? '');
        $paymentId = (int) ($payment['id'] ?? 0);
        $changed = $kind === 'wallet_topup'
            ? $this->database->markWalletTopupPaidIfWaitingGateway($paymentId)
            : $this->database->markPaymentAndPendingPaidIfWaitingGateway($paymentId);

        $this->logger->log($changed ? 'info' : 'warning', 'callback', $changed ? 'finalized' : 'duplicate_noop', 'Tetrapay callback finalize result', [
            'gateway' => 'tetrapay',
            'stage' => 'callback_finalize',
            'payment_id' => $paymentId,
            'response_payload' => $verify,
        ], $traceRef);

        return [
            'ok' => true,
            'paid' => true,
            'changed' => $changed,
            'payment_id' => $paymentId,
            'kind' => $kind,
        ];
    }
}
