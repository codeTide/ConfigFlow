<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class PhpWorkerRuntime
{
    public function __construct(private Database $database)
    {
    }

    public function runOnce(int $limit = 20): array
    {
        $jobs = $this->database->listPendingXuiJobs($limit);
        $processed = 0;
        $done = 0;
        $failed = 0;

        foreach ($jobs as $job) {
            $jobId = (int) ($job['id'] ?? 0);
            if ($jobId <= 0) {
                continue;
            }

            $start = $this->database->markXuiJobProcessing($jobId);
            if (!($start['ok'] ?? false)) {
                continue;
            }

            $processed++;
            try {
                [$clientId, $link] = $this->provisionJob($job);
                $res = $this->database->markXuiJobDone($jobId, $clientId, $link);
                if ($res['ok'] ?? false) {
                    $done++;
                } else {
                    $failed++;
                    $this->database->markXuiJobError($jobId, 'mark_done_failed');
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->database->markXuiJobError($jobId, mb_substr($e->getMessage(), 0, 500));
            }
        }

        return [
            'fetched' => count($jobs),
            'processed' => $processed,
            'done' => $done,
            'failed' => $failed,
        ];
    }

    private function provisionJob(array $job): array
    {
        $panelIp = (string) ($job['ip'] ?? '127.0.0.1');
        $panelPort = (int) ($job['port'] ?? 2053);
        $panelPatch = (string) ($job['patch'] ?? '');
        $panelUser = (string) ($job['username'] ?? '');
        $panelPass = (string) ($job['password'] ?? '');
        $pkgName = (string) ($job['pkg_name'] ?? 'XUI Service');
        $volumeGb = (float) ($job['volume_gb'] ?? 1);
        $durationDays = (int) ($job['duration_days'] ?? 30);
        $inboundId = (int) ($job['inbound_id'] ?? 1);

        if ($panelUser === '' || $panelPass === '') {
            throw new \RuntimeException('panel_credentials_missing');
        }

        $client = new XuiPanelClient($panelIp, $panelPort, $panelPatch, $panelUser, $panelPass);
        if (!$client->login()) {
            throw new \RuntimeException('xui_login_failed');
        }

        $clientId = self::uuidV4();
        $clientJson = $this->buildClientJson($clientId, $pkgName, $volumeGb, $durationDays);
        $ok = $client->addClient($inboundId, $clientJson);
        if (!$ok) {
            throw new \RuntimeException('xui_add_client_failed');
        }

        $inbound = $client->getInbound($inboundId);
        $link = $this->buildVlessLink($clientId, $panelIp, $panelPort, $pkgName, $inbound);

        return [$clientId, $link];
    }

    private function buildClientJson(string $uuid, string $pkgName, float $volumeGb, int $durationDays): string
    {
        $expireMs = (time() + max(1, $durationDays) * 86400) * 1000;
        $totalBytes = (int) round($volumeGb * 1024 * 1024 * 1024);
        $safeName = preg_replace('/[^a-zA-Z0-9\-]/', '', str_replace(' ', '-', $pkgName)) ?: 'cfg';
        $safeName = substr($safeName, 0, 20);
        $email = strtolower($safeName . '-' . substr(str_replace('-', '', $uuid), 0, 8));

        $payload = [
            'clients' => [[
                'id' => $uuid,
                'flow' => '',
                'email' => $email,
                'limitIp' => 0,
                'totalGB' => $totalBytes,
                'expiryTime' => $expireMs,
                'enable' => true,
                'tgId' => '',
                'subId' => '',
            ]],
        ];

        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private function buildVlessLink(string $uuid, string $ip, int $port, string $pkgName, array $inbound): string
    {
        $stream = [];
        if (isset($inbound['streamSettings'])) {
            $streamRaw = is_string($inbound['streamSettings']) ? $inbound['streamSettings'] : json_encode($inbound['streamSettings']);
            $tmp = json_decode((string) $streamRaw, true);
            if (is_array($tmp)) {
                $stream = $tmp;
            }
        }

        $network = (string) ($stream['network'] ?? 'tcp');
        $security = (string) ($stream['security'] ?? 'none');
        $params = [
            'type' => $network,
            'security' => $security,
        ];

        if ($network === 'ws') {
            $ws = (array) ($stream['wsSettings'] ?? []);
            $params['path'] = (string) ($ws['path'] ?? '/');
            $headers = (array) ($ws['headers'] ?? []);
            $params['host'] = (string) ($headers['Host'] ?? $ip);
        }
        if ($security === 'tls') {
            $tls = (array) ($stream['tlsSettings'] ?? []);
            $params['sni'] = (string) ($tls['serverName'] ?? $ip);
            $params['fp'] = 'chrome';
        }

        return sprintf(
            'vless://%s@%s:%d?%s#%s',
            $uuid,
            $ip,
            $port,
            http_build_query($params),
            rawurlencode($pkgName)
        );
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

final class XuiPanelClient
{
    private string $base;
    private string $cookieFile;

    public function __construct(
        private string $ip,
        private int $port,
        private string $patch,
        private string $username,
        private string $password,
    ) {
        $this->base = 'http://' . $this->ip . ':' . $this->port;
        $cleanPatch = trim($this->patch, '/');
        if ($cleanPatch !== '') {
            $this->base .= '/' . $cleanPatch;
        }
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'xui_cookie_') ?: sys_get_temp_dir() . '/xui_cookie.tmp';
    }

    public function __destruct()
    {
        if (is_file($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function login(): bool
    {
        $res = $this->request('/login', [
            'username' => $this->username,
            'password' => $this->password,
        ], false);
        return (bool) ($res['success'] ?? false);
    }

    public function addClient(int $inboundId, string $settingsJson): bool
    {
        $res = $this->request('/xui/API/inbounds/addClient', [
            'id' => $inboundId,
            'settings' => $settingsJson,
        ], true);
        return (bool) ($res['success'] ?? false);
    }

    public function getInbound(int $inboundId): array
    {
        $res = $this->request('/xui/API/inbounds/get/' . $inboundId, null, true, 'GET');
        if (!($res['success'] ?? false)) {
            return [];
        }
        return is_array($res['obj'] ?? null) ? $res['obj'] : [];
    }

    private function request(string $path, ?array $payload, bool $json, string $method = 'POST'): array
    {
        $ch = curl_init($this->base . $path);
        $headers = ['User-Agent: ConfigFlow-PHP-Worker/1.0'];
        $postFields = null;
        if ($method !== 'GET') {
            if ($json) {
                $headers[] = 'Content-Type: application/json';
                $postFields = json_encode($payload ?? [], JSON_UNESCAPED_UNICODE);
            } else {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $postFields = http_build_query($payload ?? []);
            }
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, (string) $postFields);
        }

        $raw = curl_exec($ch);
        curl_close($ch);

        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    }
}
