<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

interface WorkerApiStore
{
    public function isWorkerApiEnabled(): bool;

    public function workerApiKey(): string;
}
