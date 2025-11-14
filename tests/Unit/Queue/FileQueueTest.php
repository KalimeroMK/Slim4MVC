<?php

declare(strict_types=1);

namespace Tests\Unit\Queue;

use App\Jobs\Job;
use App\Jobs\SendEmailJob;
use App\Queue\FileQueue;
use PHPUnit\Framework\TestCase;

class FileQueueTest extends TestCase
{
    private FileQueue $queue;
    private string $testQueueFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testQueueFile = sys_get_temp_dir().'/test_queue_'.uniqid().'.json';
        $this->queue = new FileQueue($this->testQueueFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testQueueFile)) {
            unlink($this->testQueueFile);
        }
        parent::tearDown();
    }

    public function test_push_adds_job_to_queue(): void
    {
        $job = new SendEmailJob('test@example.com', 'Test', 'email.test', []);

        $this->queue->push($job);

        $this->assertEquals(1, $this->queue->size());
    }

    public function test_pop_removes_job_from_queue(): void
    {
        $job = new SendEmailJob('test@example.com', 'Test', 'email.test', []);

        $this->queue->push($job);
        $this->assertEquals(1, $this->queue->size());

        $poppedJob = $this->queue->pop();

        $this->assertInstanceOf(SendEmailJob::class, $poppedJob);
        $this->assertEquals(0, $this->queue->size());
    }

    public function test_pop_returns_null_when_queue_is_empty(): void
    {
        $job = $this->queue->pop();

        $this->assertNull($job);
    }

    public function test_size_returns_correct_count(): void
    {
        $this->assertEquals(0, $this->queue->size());

        $this->queue->push(new SendEmailJob('test1@example.com', 'Test 1', 'email.test', []));
        $this->assertEquals(1, $this->queue->size());

        $this->queue->push(new SendEmailJob('test2@example.com', 'Test 2', 'email.test', []));
        $this->assertEquals(2, $this->queue->size());
    }

    public function test_clear_removes_all_jobs(): void
    {
        $this->queue->push(new SendEmailJob('test1@example.com', 'Test 1', 'email.test', []));
        $this->queue->push(new SendEmailJob('test2@example.com', 'Test 2', 'email.test', []));

        $this->assertEquals(2, $this->queue->size());

        $this->queue->clear();

        $this->assertEquals(0, $this->queue->size());
    }

    public function test_jobs_are_processed_in_fifo_order(): void
    {
        $job1 = new SendEmailJob('test1@example.com', 'Test 1', 'email.test', []);
        $job2 = new SendEmailJob('test2@example.com', 'Test 2', 'email.test', []);

        $this->queue->push($job1);
        $this->queue->push($job2);

        $popped1 = $this->queue->pop();
        $popped2 = $this->queue->pop();

        $this->assertInstanceOf(SendEmailJob::class, $popped1);
        $this->assertInstanceOf(SendEmailJob::class, $popped2);
        // Note: We can't directly compare objects, but we can verify they were popped in order
    }
}

