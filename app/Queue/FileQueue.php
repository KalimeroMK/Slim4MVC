<?php

declare(strict_types=1);

namespace App\Queue;

use App\Jobs\Job;
use Exception;

class FileQueue implements Queue
{
    private string $queueFile;

    public function __construct(string $queuePath = null)
    {
        $queuePath = $queuePath ?? dirname(__DIR__, 2).'/storage/queue/jobs.json';
        $this->queueFile = $queuePath;

        // Ensure directory exists
        $dir = dirname($this->queueFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Create file if it doesn't exist
        if (!file_exists($this->queueFile)) {
            file_put_contents($this->queueFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    public function push(Job $job): void
    {
        $jobs = $this->loadJobs();
        $jobs[] = [
            'class' => get_class($job),
            'data' => serialize($job),
            'created_at' => time(),
        ];
        $this->saveJobs($jobs);
    }

    public function pop(): ?Job
    {
        $jobs = $this->loadJobs();

        if (empty($jobs)) {
            return null;
        }

        $jobData = array_shift($jobs);
        $this->saveJobs($jobs);

        try {
            return unserialize($jobData['data']);
        } catch (Exception $e) {
            return null;
        }
    }

    public function size(): int
    {
        return count($this->loadJobs());
    }

    public function clear(): void
    {
        $this->saveJobs([]);
    }

    /**
     * Load jobs from file.
     *
     * @return array<int, array{class: string, data: string, created_at: int}>
     */
    private function loadJobs(): array
    {
        if (!file_exists($this->queueFile)) {
            return [];
        }

        $content = file_get_contents($this->queueFile);
        if ($content === false) {
            return [];
        }

        $jobs = json_decode($content, true);
        if (!is_array($jobs)) {
            return [];
        }

        return $jobs;
    }

    /**
     * Save jobs to file.
     *
     * @param array<int, array{class: string, data: string, created_at: int}> $jobs
     */
    private function saveJobs(array $jobs): void
    {
        file_put_contents($this->queueFile, json_encode($jobs, JSON_PRETTY_PRINT), LOCK_EX);
    }
}

