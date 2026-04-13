<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

interface UiTextCatalogInterface
{
    public function success(string $message): string;

    public function error(string $message): string;

    public function warning(string $message): string;

    public function info(string $message): string;

    public function multi(UiTextBlock $block): string;

    public function paymentCreated(int $paymentId, int $amount, string $title, ?string $tip = null): string;
}
