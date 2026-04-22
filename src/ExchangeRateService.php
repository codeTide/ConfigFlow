<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class ExchangeRateService
{
    public function __construct(private Database $database)
    {
    }

    public function getLatestRate(string $source, string $symbol): ?array
    {
        return $this->database->getExchangeRate($source, $symbol);
    }

    public function getFreshRateOrNull(string $source, string $symbol, int $maxAgeSeconds): ?array
    {
        $rate = $this->getLatestRate($source, $symbol);
        if (!is_array($rate)) {
            return null;
        }

        $fetchedAt = (string) ($rate['fetched_at'] ?? '');
        $fetchedTs = strtotime($fetchedAt);
        if ($fetchedTs === false) {
            return null;
        }

        if ((time() - $fetchedTs) > $maxAgeSeconds) {
            return null;
        }

        return $rate;
    }

    public function convertTomanToUsd(int $amountToman, float $usdttmnRate): string
    {
        if ($amountToman <= 0 || $usdttmnRate <= 0) {
            return '0.001';
        }

        $rawUsd = $amountToman / $usdttmnRate;
        $rounded = ceil($rawUsd * 1000) / 1000;

        if ($rounded <= 0) {
            $rounded = 0.001;
        }

        return number_format($rounded, 3, '.', '');
    }
}
