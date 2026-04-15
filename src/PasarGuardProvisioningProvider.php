<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class PasarGuardProvisioningProvider implements ProvisioningProviderInterface
{
    private PGClient $client;

    /** @param array<int> $defaultGroupIds */
    public function __construct(string $baseUrl, string $username, string $password, private array $defaultGroupIds = [])
    {
        $this->client = new PGClient($baseUrl, $username, $password);
    }

    /** @param array<int> $groupIds */
    public function provisionUser(string $username, int $dataLimitBytes, int $expireAt, array $groupIds): array
    {
        $effectiveGroups = $groupIds !== [] ? $groupIds : $this->defaultGroupIds;
        $res = $this->client->createUser($username, $dataLimitBytes, $expireAt, $effectiveGroups);
        if (($res['success'] ?? false) !== true) {
            return [
                'ok' => false,
                'error' => (string) ($res['message'] ?? $res['errorCode'] ?? 'pasarguard_create_failed'),
            ];
        }

        $data = is_array($res['data'] ?? null) ? $res['data'] : [];
        $subscriptionUrl = trim((string) ($data['subscription_url'] ?? ''));
        if ($subscriptionUrl === '') {
            return [
                'ok' => false,
                'error' => 'pasarguard_subscription_url_missing',
            ];
        }

        return [
            'ok' => true,
            'subscription_url' => $subscriptionUrl,
            'raw' => $data,
        ];
    }

    /** @param array<string,mixed> $payload */
    public function updateUser(string $username, array $payload): array
    {
        $res = $this->client->updateUser($username, $payload);
        if (($res['success'] ?? false) !== true) {
            return [
                'ok' => false,
                'error' => (string) ($res['message'] ?? $res['errorCode'] ?? 'pasarguard_update_failed'),
            ];
        }

        return [
            'ok' => true,
            'raw' => is_array($res['data'] ?? null) ? $res['data'] : [],
        ];
    }

    public function deleteUser(string $username): array
    {
        $res = $this->client->deleteUser($username);
        if (($res['success'] ?? false) !== true) {
            return [
                'ok' => false,
                'error' => (string) ($res['message'] ?? $res['errorCode'] ?? 'pasarguard_delete_failed'),
            ];
        }

        return [
            'ok' => true,
            'raw' => is_array($res['data'] ?? null) ? $res['data'] : [],
        ];
    }
}
