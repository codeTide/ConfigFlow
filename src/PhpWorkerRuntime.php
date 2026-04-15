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
        return [
            'fetched' => 0,
            'processed' => 0,
            'done' => 0,
            'failed' => 0,
            'mode' => 'disabled',
            'message' => 'x-ui runtime is decommissioned; panel_only now provisions directly in deliverPendingOrder',
        ];
    }
}
