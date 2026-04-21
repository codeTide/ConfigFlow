<?php

declare(strict_types=1);

namespace ConfigFlow\Bot\Payments\Tetrapay;

use ConfigFlow\Bot\PaymentGatewayService;

final class TetrapayGateway
{
    public function __construct(private PaymentGatewayService $service)
    {
    }

    public function verifyByAuthority(string $authority, string $hashId = ''): array
    {
        return $this->service->verifyTetrapay($authority, $hashId);
    }
}
