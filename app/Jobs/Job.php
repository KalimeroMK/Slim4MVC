<?php

declare(strict_types=1);

namespace App\Jobs;

interface Job
{
    /**
     * Execute the job.
     */
    public function handle(): void;
}
