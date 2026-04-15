<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class PGClient
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $accessToken = '';
    private int $tokenExpireTime = 0;

    /** @var array<string,array{token:string,expire:int}> */
    private static array $cachedTokens = [];

    /** @var array<string,array{expires:int,data:array<int,mixed>}> */
    private static array $groupCache = [];

    public function __construct(string $baseUrl, string $username, string $password)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;

        $this->restoreTokenOrAuthenticate();
    }

    private function getTokenCacheKey(): string
    {
        return md5($this->baseUrl . '|' . $this->username);
    }

    private function getTokenCacheFile(): string
    {
        return dirname(__DIR__) . "/storage/tmp/pg_token_{$this->getTokenCacheKey()}.json";
    }

    private function persistTokenCache(): void
    {
        $cacheDir = dirname(__DIR__) . '/storage/tmp';
        $cacheData = [
            'token' => $this->accessToken,
            'expire' => $this->tokenExpireTime,
        ];

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        if (!is_dir($cacheDir) || !is_writable($cacheDir)) {
            return;
        }

        $cacheFile = $this->getTokenCacheFile();
        @file_put_contents($cacheFile, json_encode($cacheData), LOCK_EX);
        @chmod($cacheFile, 0644);
    }

    private function restoreTokenOrAuthenticate(): void
    {
        $cacheKey = $this->getTokenCacheKey();
        if (isset(self::$cachedTokens[$cacheKey])) {
            $cached = self::$cachedTokens[$cacheKey];
            if (($cached['expire'] ?? 0) > time() && ($cached['token'] ?? '') !== '') {
                $this->accessToken = (string) $cached['token'];
                $this->tokenExpireTime = (int) $cached['expire'];
                return;
            }
        }

        $cacheFile = $this->getTokenCacheFile();
        if (is_file($cacheFile)) {
            $cachedRaw = @file_get_contents($cacheFile);
            $cached = is_string($cachedRaw) ? json_decode($cachedRaw, true) : null;
            if (is_array($cached) && (($cached['expire'] ?? 0) > time()) && (($cached['token'] ?? '') !== '')) {
                $this->accessToken = (string) $cached['token'];
                $this->tokenExpireTime = (int) $cached['expire'];
                self::$cachedTokens[$cacheKey] = [
                    'token' => $this->accessToken,
                    'expire' => $this->tokenExpireTime,
                ];
                return;
            }
        }

        $this->authenticate();
    }

    private function handleError(array $response, string $defaultCode, string $defaultMessage): array
    {
        $httpCode = (int) ($response['httpCode'] ?? 0);
        $message = $defaultMessage;

        if ($httpCode === 422 && isset($response['data']['detail'])) {
            $detail = $response['data']['detail'];
            if (is_array($detail) && isset($detail[0]) && is_array($detail[0])) {
                $details = array_map(static function ($d): string {
                    if (!is_array($d)) {
                        return '';
                    }
                    $locPart = '';
                    if (isset($d['loc'])) {
                        if (is_array($d['loc']) && isset($d['loc'][0])) {
                            $locPart = (string) $d['loc'][0];
                        } elseif (is_string($d['loc'])) {
                            $locPart = $d['loc'];
                        }
                    }
                    $msgPart = (string) ($d['msg'] ?? '');
                    return trim("{$locPart}: {$msgPart}");
                }, $detail);
                $details = array_values(array_filter($details, static fn ($v): bool => $v !== ''));
                if ($details !== []) {
                    $message = implode(', ', $details);
                }
            } elseif (is_array($detail)) {
                $pairs = [];
                foreach ($detail as $field => $msg) {
                    $pairs[] = (string) $field . ': ' . (string) $msg;
                }
                if ($pairs !== []) {
                    $message = implode(', ', $pairs);
                }
            } elseif (is_string($detail) && $detail !== '') {
                $message = $detail;
            }
        }

        return [
            'success' => false,
            'httpCode' => $httpCode,
            'errorCode' => $defaultCode . '_' . $httpCode,
            'message' => $message,
        ];
    }

    private function authenticate(): array
    {
        $response = $this->sendRequest('/api/admin/token', 'POST', [
            'username' => $this->username,
            'password' => $this->password,
        ], true);

        if (($response['httpCode'] ?? 0) === 200 && isset($response['data']['access_token'])) {
            $this->accessToken = (string) $response['data']['access_token'];
            $this->tokenExpireTime = time() + 3600;

            $cacheData = [
                'token' => $this->accessToken,
                'expire' => $this->tokenExpireTime,
            ];
            self::$cachedTokens[$this->getTokenCacheKey()] = $cacheData;
            $this->persistTokenCache();

            return [
                'success' => true,
                'accessToken' => $this->accessToken,
                'tokenType' => (string) ($response['data']['token_type'] ?? 'bearer'),
            ];
        }

        return $this->handleError($response, 'PG_AUTH_ERROR', 'Authentication failed');
    }

    private function sendRequest(string $endpoint, string $method = 'GET', array $data = [], bool $isAuth = false): array
    {
        $startedAt = microtime(true);
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        $headers = [];
        if ($isAuth) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);

        if ($data !== []) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $isAuth ? http_build_query($data) : json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->logPerformance($endpoint, $method, $httpCode, $durationMs, false, $error);
            return [
                'success' => false,
                'httpCode' => $httpCode,
                'errorCode' => 'PG_CURL_ERROR',
                'message' => $error,
            ];
        }

        curl_close($ch);
        $responseData = is_string($response) ? json_decode($response, true) : null;
        $this->logPerformance($endpoint, $method, $httpCode, $durationMs, $httpCode >= 200 && $httpCode < 300);

        if ($httpCode === 401 && !$isAuth) {
            $auth = $this->authenticate();
            if (($auth['success'] ?? false) === true) {
                return $this->sendRequest($endpoint, $method, $data, $isAuth);
            }
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'httpCode' => $httpCode,
                'data' => is_array($responseData) ? $responseData : [],
            ];
        }

        return [
            'success' => false,
            'httpCode' => $httpCode,
            'data' => is_array($responseData) ? $responseData : [],
        ];
    }

    private function logPerformance(string $endpoint, string $method, int $httpCode, int $durationMs, bool $ok, ?string $error = null): void
    {
        $logDir = dirname(__DIR__) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $line = [
            'ts' => gmdate('c'),
            'baseUrl' => $this->baseUrl,
            'endpoint' => $endpoint,
            'method' => $method,
            'httpCode' => $httpCode,
            'durationMs' => $durationMs,
            'ok' => $ok,
            'error' => $error,
        ];

        @file_put_contents(
            $logDir . '/pgclient_perf.log',
            json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    /** @param array<int> $groupIDs */
    public function createUser(string $code, int $traffic, int $expire, array $groupIDs = []): array
    {
        if ($groupIDs === []) {
            return [
                'success' => false,
                'errorCode' => 'PG_NO_GROUP_IDS',
                'message' => 'Groups IDs cannot be empty',
            ];
        }

        $response = $this->sendRequest('/api/user', 'POST', [
            'username' => $code,
            'expire' => $expire,
            'data_limit_reset_strategy' => 'no_reset',
            'data_limit' => $traffic,
            'group_ids' => $groupIDs,
        ]);

        if (($response['success'] ?? false) && isset($response['data']['subscription_url'])) {
            return [
                'success' => true,
                'httpCode' => (int) ($response['httpCode'] ?? 200),
                'data' => $response['data'],
            ];
        }

        return $this->handleError($response, 'PG_CREATE_ERROR', 'Failed to create user');
    }

    /** @param array<string,mixed> $data */
    public function updateUser(string $code, array $data): array
    {
        $response = $this->sendRequest('/api/user/' . rawurlencode($code), 'PUT', $data);
        if (($response['success'] ?? false) === true) {
            return [
                'success' => true,
                'httpCode' => (int) ($response['httpCode'] ?? 200),
                'data' => $response['data'] ?? [],
            ];
        }

        return $this->handleError($response, 'PG_UPDATE_ERROR', 'Failed to update user');
    }

    public function resetUserUsage(string $code): array
    {
        $response = $this->sendRequest('/api/user/' . rawurlencode($code) . '/reset', 'POST');
        if (($response['success'] ?? false) === true) {
            return [
                'success' => true,
                'httpCode' => (int) ($response['httpCode'] ?? 200),
                'data' => $response['data'] ?? [],
            ];
        }

        return $this->handleError($response, 'PG_RESET_ERROR', 'Failed to reset user usage');
    }

    public function deleteUser(string $code): array
    {
        $response = $this->sendRequest('/api/user/' . rawurlencode($code), 'DELETE');
        if (($response['success'] ?? false) === true) {
            return [
                'success' => true,
                'httpCode' => (int) ($response['httpCode'] ?? 200),
                'data' => $response['data'] ?? [],
            ];
        }

        return $this->handleError($response, 'PG_DELETE_ERROR', 'Failed to delete user');
    }

    public function getUsers(int $batchSize = 2000): array
    {
        $offset = 0;
        $allUsers = [];

        while (true) {
            $endpoint = '/api/users?offset=' . $offset . '&limit=' . $batchSize;
            $response = $this->sendRequest($endpoint);
            if (($response['success'] ?? false) !== true) {
                return $this->handleError($response, 'PG_GET_USERS_ERROR', 'Failed to get users');
            }

            $users = is_array($response['data']['users'] ?? null) ? $response['data']['users'] : [];
            $allUsers = array_merge($allUsers, $users);
            if (count($users) < $batchSize) {
                break;
            }
            sleep(2);
            $offset += $batchSize;
        }

        return [
            'success' => true,
            'httpCode' => 200,
            'data' => ['users' => $allUsers],
        ];
    }

    public function getUser(string $code): array
    {
        $response = $this->sendRequest('/api/user/' . rawurlencode($code));
        if (($response['success'] ?? false) === true) {
            return [
                'success' => true,
                'httpCode' => (int) ($response['httpCode'] ?? 200),
                'data' => $response['data'] ?? [],
            ];
        }

        return $this->handleError($response, 'PG_GET_USER_ERROR', 'Failed to get user');
    }

    public function getGroups(int $offset = 0, int $limit = 100): array
    {
        $cacheKey = md5($this->baseUrl . '|' . $this->username . '|' . $offset . '|' . $limit);
        $now = time();

        if (isset(self::$groupCache[$cacheKey]) && (self::$groupCache[$cacheKey]['expires'] ?? 0) > $now) {
            return ['success' => true, 'httpCode' => 200, 'data' => self::$groupCache[$cacheKey]['data']];
        }

        $cacheFile = dirname(__DIR__) . '/storage/tmp/pg_groups_' . $cacheKey . '.json';
        if (is_file($cacheFile)) {
            $cachedRaw = @file_get_contents($cacheFile);
            $cached = is_string($cachedRaw) ? json_decode($cachedRaw, true) : null;
            if (is_array($cached) && (int) ($cached['expires'] ?? 0) > $now && isset($cached['data'])) {
                self::$groupCache[$cacheKey] = [
                    'expires' => (int) $cached['expires'],
                    'data' => is_array($cached['data']) ? $cached['data'] : [],
                ];
                return ['success' => true, 'httpCode' => 200, 'data' => self::$groupCache[$cacheKey]['data']];
            }
        }

        $response = $this->sendRequest('/api/groups?offset=' . $offset . '&limit=' . $limit);
        if (($response['success'] ?? false) === true) {
            $groups = is_array($response['data']['groups'] ?? null) ? $response['data']['groups'] : [];
            $payload = ['expires' => $now + 300, 'data' => $groups];
            self::$groupCache[$cacheKey] = $payload;
            @file_put_contents($cacheFile, json_encode($payload), LOCK_EX);
            return ['success' => true, 'httpCode' => (int) ($response['httpCode'] ?? 200), 'data' => $groups];
        }

        return $this->handleError($response, 'PG_GET_GROUPS_ERROR', 'Failed to get groups');
    }

    public function revokeSubscription(string $code): array
    {
        $response = $this->sendRequest('/api/user/' . rawurlencode($code) . '/revoke_sub', 'POST');
        if (($response['success'] ?? false) === true) {
            return [
                'success' => true,
                'httpCode' => (int) ($response['httpCode'] ?? 200),
                'data' => $response['data'] ?? [],
            ];
        }

        return $this->handleError($response, 'PG_REVOKE_SUB_ERROR', 'Failed to revoke user subscription');
    }
}
