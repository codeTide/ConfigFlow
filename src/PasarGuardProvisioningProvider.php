<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

use ConfigFlow\Bot\Support\AppLogger;
use ConfigFlow\Bot\Support\ErrorRef;

final class PasarGuardProvisioningProvider implements ProvisioningProviderInterface
{
    private PGClient $client;
    private AppLogger $logger;

    /** @param array<int> $defaultGroupIds */
    public function __construct(string $baseUrl, string $username, string $password, private array $defaultGroupIds = [])
    {
        $this->client = new PGClient($baseUrl, $username, $password);
        $this->logger = new AppLogger();
    }

    /** @param array<int> $groupIds */
    public function provisionUser(string $username, int $dataLimitBytes, int $expireAt, array $groupIds): array
    {
        $effectiveGroups = $groupIds !== [] ? $groupIds : $this->defaultGroupIds;
        $res = $this->client->createUser($username, $dataLimitBytes, $expireAt, $effectiveGroups);
        if (($res['success'] ?? false) !== true) {
            $ref = $this->logger->log('error', 'panel', 'panel_provision_failed', 'Panel create user failed', [
                'stage' => 'provision_create_user',
                'provider_error' => (string) ($res['message'] ?? $res['errorCode'] ?? 'pasarguard_create_failed'),
                'response_payload' => $res,
                'request_payload' => ['username' => $username, 'expire_at' => $expireAt],
            ], ErrorRef::make('PANEL'));
            return [
                'ok' => false,
                'error' => (string) ($res['message'] ?? $res['errorCode'] ?? 'pasarguard_create_failed'),
                'error_ref' => $ref,
            ];
        }

        $data = is_array($res['data'] ?? null) ? $res['data'] : [];
        $subscriptionUrl = trim((string) ($data['subscription_url'] ?? ''));
        if ($subscriptionUrl === '') {
            $ref = $this->logger->log('error', 'panel', 'panel_subscription_missing', 'Panel subscription url missing', [
                'stage' => 'provision_create_user',
                'response_payload' => $data,
                'request_payload' => ['username' => $username],
            ], ErrorRef::make('PANEL'));
            return [
                'ok' => false,
                'error' => 'pasarguard_subscription_url_missing',
                'error_ref' => $ref,
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
            $ref = $this->logger->log('error', 'panel', 'panel_update_failed', 'Panel update user failed', [
                'stage' => 'provision_update_user',
                'provider_error' => (string) ($res['message'] ?? $res['errorCode'] ?? 'pasarguard_update_failed'),
                'request_payload' => ['username' => $username, 'payload' => $payload],
                'response_payload' => $res,
            ], ErrorRef::make('PANEL'));
            return [
                'ok' => false,
                'error' => (string) ($res['message'] ?? $res['errorCode'] ?? 'pasarguard_update_failed'),
                'error_ref' => $ref,
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
            $ref = $this->logger->log('error', 'panel', 'panel_delete_failed', 'Panel delete user failed', [
                'stage' => 'provision_delete_user',
                'provider_error' => (string) ($res['message'] ?? $res['errorCode'] ?? 'pasarguard_delete_failed'),
                'request_payload' => ['username' => $username],
                'response_payload' => $res,
            ], ErrorRef::make('PANEL'));
            return [
                'ok' => false,
                'error' => (string) ($res['message'] ?? $res['errorCode'] ?? 'pasarguard_delete_failed'),
                'error_ref' => $ref,
            ];
        }

        return [
            'ok' => true,
            'raw' => is_array($res['data'] ?? null) ? $res['data'] : [],
        ];
    }
}
