<?php

declare(strict_types=1);

namespace ConfigFlow\Bot\Payments\Tetrapay;

use ConfigFlow\Bot\Database;

final class TetrapayCallbackHandler
{
    public function __construct(
        private Database $database,
        private TetrapayGateway $gateway
    ) {
    }

    /** @param array<string,mixed> $payload */
    public function handle(array $payload): array
    {
        $authority = trim((string) ($payload['Authority'] ?? $payload['authority'] ?? ''));
        if ($authority === '') {
            return ['ok' => false, 'error' => 'missing_authority'];
        }

        $payment = $this->database->getPaymentByGatewayRef($authority, 'tetrapay');
        if (!is_array($payment)) {
            return ['ok' => false, 'error' => 'payment_not_found'];
        }

        $providerPayload = json_decode((string) ($payment['provider_payload'] ?? ''), true);
        $hashId = is_array($providerPayload) ? trim((string) ($providerPayload['hash_id'] ?? '')) : '';
        $verify = $this->gateway->verifyByAuthority($authority, $hashId);
        if (!(bool) ($verify['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($verify['error'] ?? 'verify_failed')];
        }
        if (!(bool) ($verify['paid'] ?? false)) {
            return ['ok' => true, 'paid' => false, 'status' => (int) ($verify['status'] ?? 0), 'payment_id' => (int) ($payment['id'] ?? 0)];
        }

        $kind = (string) ($payment['kind'] ?? '');
        $paymentId = (int) ($payment['id'] ?? 0);
        $changed = $kind === 'wallet_topup'
            ? $this->database->markWalletTopupPaidIfWaitingGateway($paymentId)
            : $this->database->markPaymentAndPendingPaidIfWaitingGateway($paymentId);

        return [
            'ok' => true,
            'paid' => true,
            'changed' => $changed,
            'payment_id' => $paymentId,
            'kind' => $kind,
        ];
    }
}
