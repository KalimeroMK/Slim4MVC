<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Jobs;

abstract class BaseJob implements Job
{
    protected int $attempts = 0;
    protected int $maxAttempts = 3;

    /**
     * Get the number of times the job has been attempted.
     */
    public function attempts(): int
    {
        return $this->attempts;
    }

    /**
     * Increment the number of attempts.
     */
    public function incrementAttempts(): void
    {
        $this->attempts++;
    }

    /**
     * Get the maximum number of times the job may be attempted.
     */
    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Set the maximum number of times the job may be attempted.
     */
    public function setMaxAttempts(int $maxAttempts): void
    {
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Determine if the job should be retried on failure.
     */
    public function shouldRetry(): bool
    {
        return $this->attempts < $this->maxAttempts;
    }
}

