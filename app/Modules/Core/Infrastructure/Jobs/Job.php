<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Jobs;

interface Job
{
    /**
     * Execute the job.
     */
    public function handle(): void;

    /**
     * Get the number of times the job has been attempted.
     */
    public function attempts(): int;

    /**
     * Get the maximum number of times the job may be attempted.
     */
    public function maxAttempts(): int;

    /**
     * Determine if the job should be retried on failure.
     */
    public function shouldRetry(): bool;
}
