<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Queue;

use App\Modules\Core\Infrastructure\Jobs\Job;
use Exception;
use Predis\Client;

class RedisQueue implements Queue
{
    private readonly Client $client;

    private readonly string $queueName;

    public function __construct(?Client $client = null, string $queueName = 'default')
    {
        $this->client = $client ?? $this->createRedisClient();
        $this->queueName = 'queue:'.$queueName;
    }

    public function push(Job $job): void
    {
        $jobData = [
            'class' => $job::class,
            'data' => serialize($job),
            'created_at' => time(),
            'attempts' => 0,
        ];

        $json = json_encode($jobData);
        $this->client->lpush($this->queueName, [$json !== false ? $json : '{}']);
    }

    public function pop(): ?Job
    {
        // Use rpop for non-blocking pop (returns null if queue is empty)
        $result = $this->client->rpop($this->queueName);

        if ($result === null) {
            return null;
        }

        $jobData = json_decode($result, true);

        if (! is_array($jobData)) {
            return null;
        }

        try {
            return unserialize($jobData['data']);
        } catch (Exception) {
            return null;
        }
    }

    public function size(): int
    {
        return (int) $this->client->llen($this->queueName);
    }

    public function clear(): void
    {
        $this->client->del($this->queueName);
    }

    /**
     * Get queue statistics.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return [
            'size' => $this->size(),
            'queue_name' => $this->queueName,
        ];
    }

    /**
     * Create Redis client from environment configuration.
     */
    private function createRedisClient(): Client
    {
        $host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
        $port = (int) ($_ENV['REDIS_PORT'] ?? 6379);
        $password = $_ENV['REDIS_PASSWORD'] ?? null;
        $database = (int) ($_ENV['REDIS_DATABASE'] ?? 0);

        $parameters = [
            'host' => $host,
            'port' => $port,
            'database' => $database,
        ];

        if ($password !== null) {
            $parameters['password'] = $password;
        }

        return new Client($parameters);
    }
}
