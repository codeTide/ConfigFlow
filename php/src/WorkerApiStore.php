<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

interface WorkerApiStore
{
    public function isWorkerApiEnabled(): bool;

    public function workerApiKey(): string;

    public function listPendingXuiJobs(int $limit = 20): array;

    /** @return array{ok:bool,error?:string,status?:string} */
    public function markXuiJobProcessing(int $jobId): array;

    /** @return array{ok:bool,error?:string} */
    public function markXuiJobDone(int $jobId, string $resultConfig, string $resultLink): array;

    /** @return array{ok:bool,error?:string,retry_count?:int} */
    public function markXuiJobError(int $jobId, string $errorMsg): array;

    public function getXuiJob(int $jobId): ?array;
}
