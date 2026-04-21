<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class PaymentGatewayService
{
    private const TETRAPAY_CREATE_URL = 'https://tetra98.com/api/create_order';
    private const TETRAPAY_VERIFY_URL = 'https://tetra98.com/api/verify';

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

        $response = $this->postJson(self::TETRAPAY_CREATE_URL, $payload);
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

        $response = $this->postJson(self::TETRAPAY_VERIFY_URL, [
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

    public function createSwapwalletCryptoInvoice(int $amount, string $orderId, string $network = 'TRON', string $description = 'Payment'): array
    {
        $apiKey = trim($this->settings->get('swapwallet_crypto_api_key', ''));
        $username = ltrim(trim($this->settings->get('swapwallet_crypto_username', '')), '@');
        if ($apiKey === '' || $username === '') {
            return ['ok' => false, 'error' => 'swapwallet_credentials_missing'];
        }
        if (str_starts_with(strtolower($apiKey), 'bearer ')) {
            $apiKey = trim(substr($apiKey, 7));
        }

        $allowedToken = strtoupper($network) === 'TON' ? 'TON' : 'USDT';
        $payload = [
            'amount' => ['number' => (string) $amount, 'unit' => 'IRT'],
            'network' => strtoupper($network),
            'allowedToken' => $allowedToken,
            'ttl' => 3600,
            'orderId' => (string) $orderId,
            'description' => $description,
        ];
        $url = rtrim(Config::swapwalletBaseUrl(), '/') . '/v2/payment/' . rawurlencode($username) . '/invoices/temporary-wallet';
        $res = $this->postJsonHeaders($url, $payload, [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
        ]);
        if (!($res['ok'] ?? false)) {
            return ['ok' => false, 'error' => 'request_failed'];
        }
        $data = $res['data'] ?? [];
        if (($data['status'] ?? '') !== 'OK' || !is_array($data['result'] ?? null)) {
            return ['ok' => false, 'error' => 'invalid_response', 'raw' => $data];
        }
        $result = $data['result'];
        $links = is_array($result['links'] ?? null) ? $result['links'] : [];
        $firstUrl = '';
        foreach ($links as $link) {
            if (is_array($link) && trim((string) ($link['url'] ?? '')) !== '') {
                $firstUrl = trim((string) $link['url']);
                break;
            }
        }
        return [
            'ok' => true,
            'invoice_id' => (string) ($result['id'] ?? ''),
            'pay_url' => $firstUrl,
            'wallet_address' => (string) ($result['walletAddress'] ?? ''),
            'links' => $links,
            'raw' => $result,
        ];
    }

    public function checkSwapwalletCryptoInvoice(string $invoiceId): array
    {
        $apiKey = trim($this->settings->get('swapwallet_crypto_api_key', ''));
        $username = ltrim(trim($this->settings->get('swapwallet_crypto_username', '')), '@');
        if ($apiKey === '' || $username === '' || trim($invoiceId) === '') {
            return ['ok' => false, 'error' => 'missing_data'];
        }
        if (str_starts_with(strtolower($apiKey), 'bearer ')) {
            $apiKey = trim(substr($apiKey, 7));
        }
        $url = rtrim(Config::swapwalletBaseUrl(), '/') . '/v2/payment/' . rawurlencode($username) . '/invoices/' . rawurlencode($invoiceId);
        $res = $this->getJsonHeaders($url, [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
        ]);
        if (!($res['ok'] ?? false)) {
            return ['ok' => false, 'error' => 'request_failed'];
        }
        $data = $res['data'] ?? [];
        $result = is_array($data['result'] ?? null) ? $data['result'] : [];
        $status = strtoupper((string) ($result['status'] ?? $data['status'] ?? ''));
        $isPaid = in_array($status, ['PAID', 'COMPLETED', 'SUCCESS'], true);
        return ['ok' => true, 'paid' => $isPaid, 'raw' => $data];
    }

    public function createTronpaysRialInvoice(int $amount, string $hashId): array
    {
        $apiKey = trim($this->settings->get('tronpays_rial_api_key', ''));
        $callback = trim($this->settings->get('tronpays_rial_callback_url', ''));
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'tronpays_api_key_missing'];
        }
        $payload = [
            'api_key' => $apiKey,
            'hash_id' => substr(md5($hashId), 0, 20),
            'amount' => $amount,
            'callback_url' => $callback !== '' ? $callback : 'https://example.com/',
        ];
        $url = rtrim(Config::tronpaysBaseUrl(), '/') . '/api/invoice/create';
        $res = $this->postJsonHeaders($url, $payload, ['Accept: application/json']);
        if (!($res['ok'] ?? false)) {
            return ['ok' => false, 'error' => 'request_failed'];
        }
        $data = $res['data'] ?? [];
        return [
            'ok' => true,
            'invoice_id' => (string) ($data['invoice_id'] ?? $data['invoiceId'] ?? ''),
            'pay_url' => (string) ($data['invoice_url'] ?? $data['invoiceUrl'] ?? ''),
            'raw' => $data,
        ];
    }

    public function checkTronpaysRialInvoice(string $invoiceId): array
    {
        $apiKey = trim($this->settings->get('tronpays_rial_api_key', ''));
        if ($apiKey === '' || trim($invoiceId) === '') {
            return ['ok' => false, 'error' => 'missing_data'];
        }
        $url = rtrim(Config::tronpaysBaseUrl(), '/') . '/api/invoice/check';
        $res = $this->postJsonHeaders($url, [
            'api_key' => $apiKey,
            'invoice_id' => $invoiceId,
        ], ['Accept: application/json']);
        if (!($res['ok'] ?? false)) {
            return ['ok' => false, 'error' => 'request_failed'];
        }
        $data = $res['data'] ?? [];
        $status = strtoupper((string) ($data['status'] ?? $data['state'] ?? $data['payment_status'] ?? (is_string($data) ? $data : '')));
        $isPaid = in_array($status, ['PAID', 'SUCCESS', 'SUCCESSFUL', 'COMPLETED', 'DONE'], true);
        return ['ok' => true, 'paid' => $isPaid, 'raw' => $data];
    }

    public function cryptoAddress(string $coin): string
    {
        return trim($this->settings->get('crypto_wallet_' . $coin, ''));
    }

    public function verifyCryptoTransaction(string $coin, string $txHash): array
    {
        $coin = strtolower(trim($coin));
        $txHash = trim($txHash);
        if ($txHash === '') {
            return ['ok' => false, 'error' => 'missing_tx_hash'];
        }

        if ($coin === 'ltc') {
            $url = 'https://api.blockcypher.com/v1/ltc/main/txs/' . rawurlencode($txHash);
            $res = $this->getJson($url);
            if (!($res['ok'] ?? false)) {
                return ['ok' => false, 'error' => 'request_failed'];
            }
            $data = $res['data'] ?? [];
            $confirmed = ((int) ($data['confirmations'] ?? 0)) > 0;
            return ['ok' => true, 'confirmed' => $confirmed, 'raw' => $data];
        }

        if ($coin === 'tron') {
            $url = 'https://apilist.tronscanapi.com/api/transaction-info?hash=' . rawurlencode($txHash);
            $res = $this->getJson($url);
            if (!($res['ok'] ?? false)) {
                return ['ok' => false, 'error' => 'request_failed'];
            }
            $data = $res['data'] ?? [];
            $confirmed = (bool) ($data['confirmed'] ?? false);
            $amount = null;
            if (isset($data['contractData']) && is_array($data['contractData'])) {
                $sunAmount = (float) ($data['contractData']['amount'] ?? 0);
                if ($sunAmount > 0) {
                    $amount = $sunAmount / 1000000.0;
                }
            }
            return ['ok' => true, 'confirmed' => $confirmed, 'raw' => $data, 'transfer_amount' => $amount];
        }

        if ($coin === 'ton') {
            $url = 'https://tonapi.io/v2/blockchain/transactions/' . rawurlencode($txHash);
            $res = $this->getJson($url);
            if (!($res['ok'] ?? false)) {
                return ['ok' => false, 'error' => 'request_failed'];
            }
            $data = $res['data'] ?? [];
            $confirmed = (bool) ($data['success'] ?? false);
            $amount = null;
            $inMsg = $data['in_msg'] ?? [];
            if (is_array($inMsg)) {
                $nano = (float) ($inMsg['value'] ?? 0);
                if ($nano > 0) {
                    $amount = $nano / 1000000000.0;
                }
            }
            return ['ok' => true, 'confirmed' => $confirmed, 'raw' => $data, 'transfer_amount' => $amount];
        }

        if ($coin === 'usdt_bep20' || $coin === 'usdc_bep20') {
            $apiKey = trim($this->settings->get('bscscan_api_key', ''));
            $query = http_build_query([
                'module' => 'proxy',
                'action' => 'eth_getTransactionReceipt',
                'txhash' => $txHash,
                'apikey' => $apiKey,
            ]);
            $url = 'https://api.bscscan.com/api?' . $query;
            $res = $this->getJson($url);
            if (!($res['ok'] ?? false)) {
                return ['ok' => false, 'error' => 'request_failed'];
            }
            $data = $res['data'] ?? [];
            $receipt = $data['result'] ?? [];
            $confirmed = is_array($receipt) && (($receipt['status'] ?? '') === '0x1');
            $transferAmount = null;
            $tokenQuery = http_build_query([
                'module' => 'account',
                'action' => 'tokentx',
                'txhash' => $txHash,
                'page' => 1,
                'offset' => 1,
                'sort' => 'desc',
                'apikey' => $apiKey,
            ]);
            $tokenRes = $this->getJson('https://api.bscscan.com/api?' . $tokenQuery);
            if (($tokenRes['ok'] ?? false) && is_array($tokenRes['data'] ?? null)) {
                $rows = $tokenRes['data']['result'] ?? [];
                if (is_array($rows) && isset($rows[0]) && is_array($rows[0])) {
                    $row = $rows[0];
                    $valueRaw = (string) ($row['value'] ?? '0');
                    $decimals = (int) ($row['tokenDecimal'] ?? 18);
                    if ($valueRaw !== '' && ctype_digit($valueRaw) && $decimals >= 0 && $decimals <= 36) {
                        $transferAmount = ((float) $valueRaw) / (10 ** $decimals);
                    }
                }
            }
            return ['ok' => true, 'confirmed' => $confirmed, 'raw' => $data, 'transfer_amount' => $transferAmount];
        }

        return ['ok' => false, 'error' => 'coin_not_supported_yet'];
    }

    public function validateClaimedAmount(string $coin, int $amountToman, ?float $claimedAmountCoin): array
    {
        if ($claimedAmountCoin === null || $claimedAmountCoin <= 0) {
            return ['ok' => false, 'error' => 'claimed_amount_missing'];
        }

        $usdtToman = (int) ($this->settings->get('crypto_usdt_toman_rate', '90000') ?: '90000');
        if ($usdtToman <= 0) {
            return ['ok' => false, 'error' => 'invalid_usdt_rate'];
        }

        $priceUsdt = $this->coinPriceUsdt($coin);
        if ($priceUsdt <= 0) {
            return ['ok' => false, 'error' => 'price_unavailable'];
        }

        $expectedCoin = ($amountToman / $usdtToman) / $priceUsdt;
        $tolerance = max($expectedCoin * 0.05, 0.000001); // 5%
        $delta = abs($claimedAmountCoin - $expectedCoin);

        return [
            'ok' => true,
            'expected_coin' => $expectedCoin,
            'claimed_coin' => $claimedAmountCoin,
            'delta' => $delta,
            'amount_match' => $delta <= $tolerance,
        ];
    }

    public function resolveEffectivePaidAmount(array $verifyResult, ?float $claimedAmountCoin): ?float
    {
        $onChain = $verifyResult['transfer_amount'] ?? null;
        if (is_numeric($onChain)) {
            $value = (float) $onChain;
            if ($value > 0) {
                return $value;
            }
        }

        if ($claimedAmountCoin !== null && $claimedAmountCoin > 0) {
            return $claimedAmountCoin;
        }

        return null;
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
        curl_close($ch);
        if ($raw === false || $err !== '') {
            return ['ok' => false, 'error' => $err];
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'decode_error'];
        }
        return ['ok' => true, 'data' => $decoded];
    }

    private function getJsonHeaders(string $url, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $err !== '') {
            return ['ok' => false, 'error' => $err];
        }
        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'decode_error'];
        }
        return ['ok' => true, 'data' => $decoded];
    }

    private function getJson(string $url): array
    {
        $lastErr = '';
        for ($i = 0; $i < 3; $i++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);
            $raw = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($raw !== false && $err === '') {
                $decoded = json_decode((string) $raw, true);
                if (is_array($decoded)) {
                    return ['ok' => true, 'data' => $decoded];
                }
                $lastErr = 'decode_error';
            } else {
                $lastErr = $err;
            }

            usleep(250000); // 250ms retry backoff
        }

        return ['ok' => false, 'error' => $lastErr];
    }

    private function coinPriceUsdt(string $coin): float
    {
        $map = [
            'tron' => 'TRXUSDT',
            'ton' => 'TONUSDT',
            'ltc' => 'LTCUSDT',
            'usdt_bep20' => 'USDTUSDT',
            'usdc_bep20' => 'USDCUSDT',
        ];
        $symbol = $map[strtolower($coin)] ?? '';
        if ($symbol === '') {
            return 0.0;
        }
        if ($symbol === 'USDTUSDT') {
            return 1.0;
        }
        if ($symbol === 'USDCUSDT') {
            return 1.0;
        }

        $res = $this->getJson('https://api.binance.com/api/v3/ticker/price?symbol=' . rawurlencode($symbol));
        if (!($res['ok'] ?? false)) {
            return 0.0;
        }
        return (float) (($res['data']['price'] ?? 0));
    }
}
