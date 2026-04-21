<?php

declare(strict_types=1);

namespace ConfigFlow\Bot\Payments\Nowpayments;

use ConfigFlow\Bot\PaymentGatewayService;

final class NowpaymentsGateway
{
    public function __construct(private PaymentGatewayService $service)
    {
    }

    public function createInvoice(int $amount, string $orderId, string $description, array $options = []): array
    {
        return $this->service->createNowpaymentsInvoice($amount, $orderId, $description, $options);
    }
}
