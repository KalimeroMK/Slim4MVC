<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Queue;

use InvalidArgumentException;
use Predis\Client;

class QueueManager
{
    private readonly string $defaultDriver;

    public function __construct(?string $defaultDriver = null)
    {
        $this->defaultDriver = $defaultDriver ?? $_ENV['QUEUE_DRIVER'] ?? 'file';
    }

    /**
     * Get queue instance based on driver.
     */
    public function queue(?string $driver = null, ?string $queueName = null): Queue
    {
        $driver ??= $this->defaultDriver;

        return match ($driver) {
            'redis' => $this->createRedisQueue($queueName),
            'file' => $this->createFileQueue(),
            default => throw new InvalidArgumentException('Unsupported queue driver: '.$driver),
        };
    }

    /**
     * Create Redis queue instance.
     */
    private function createRedisQueue(?string $queueName = null): RedisQueue
    {
        $client = $this->createRedisClient();
        $queueName ??= $_ENV['QUEUE_NAME'] ?? 'default';

        return new RedisQueue($client, $queueName);
    }

    /**
     * Create file queue instance.
     */
    private function createFileQueue(): FileQueue
    {
        return new FileQueue();
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
