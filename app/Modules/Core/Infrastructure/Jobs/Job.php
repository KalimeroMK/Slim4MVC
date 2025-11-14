<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Jobs;

interface Job
{
    /**
     * Execute the job.
     */
    public function handle(): void;
}
