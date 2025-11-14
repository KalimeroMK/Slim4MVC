<?php

declare(strict_types=1);

namespace App\Queue;

use App\Jobs\Job;

interface Queue
{
    /**
     * Push a job onto the queue.
     */
    public function push(Job $job): void;

    /**
     * Pop a job from the queue.
     */
    public function pop(): ?Job;

    /**
     * Get the number of jobs in the queue.
     */
    public function size(): int;

    /**
     * Clear all jobs from the queue.
     */
    public function clear(): void;
}
