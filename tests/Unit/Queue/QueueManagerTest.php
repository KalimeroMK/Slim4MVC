<?php

declare(strict_types=1);

namespace Tests\Unit\Queue;

use App\Modules\Core\Infrastructure\Queue\QueueManager;
use App\Modules\Core\Infrastructure\Queue\FileQueue;
use App\Modules\Core\Infrastructure\Queue\RedisQueue;
use PHPUnit\Framework\TestCase;

class QueueManagerTest extends TestCase
{
    public function test_queue_manager_class_exists(): void
    {
        $this->assertTrue(class_exists(QueueManager::class));
    }

    public function test_queue_manager_has_queue_method(): void
    {
        $this->assertTrue(method_exists(QueueManager::class, 'queue'));
    }

    public function test_file_queue_class_exists(): void
    {
        $this->assertTrue(class_exists(FileQueue::class));
    }

    public function test_redis_queue_class_exists(): void
    {
        $this->assertTrue(class_exists(RedisQueue::class));
    }
}
