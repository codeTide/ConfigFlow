<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

interface ProvisioningProviderInterface
{
    /**
     * @param array<int> $groupIds
     * @return array{ok:bool,subscription_url?:string,raw?:array<string,mixed>,error?:string}
     */
    public function provisionUser(string $username, int $dataLimitBytes, int $expireAt, array $groupIds): array;

    /** @param array<string,mixed> $payload */
    public function updateUser(string $username, array $payload): array;

    public function deleteUser(string $username): array;
}
