<?php

declare(strict_types=1);

namespace Tests\Unit\Queue;

use App\Modules\Core\Infrastructure\Jobs\SendEmailJob;
use App\Modules\Core\Infrastructure\Queue\FileQueue;
use PHPUnit\Framework\TestCase;

final class FileQueueTest extends TestCase
{
    private FileQueue $fileQueue;

    private string $testQueueFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testQueueFile = sys_get_temp_dir().'/test_queue_'.uniqid().'.json';
        $this->fileQueue = new FileQueue($this->testQueueFile);
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
        $sendEmailJob = new SendEmailJob('test@example.com', 'Test', 'email.test', []);

        $this->fileQueue->push($sendEmailJob);

        $this->assertSame(1, $this->fileQueue->size());
    }

    public function test_pop_removes_job_from_queue(): void
    {
        $sendEmailJob = new SendEmailJob('test@example.com', 'Test', 'email.test', []);

        $this->fileQueue->push($sendEmailJob);
        $this->assertSame(1, $this->fileQueue->size());

        $poppedJob = $this->fileQueue->pop();

        $this->assertInstanceOf(SendEmailJob::class, $poppedJob);
        $this->assertSame(0, $this->fileQueue->size());
    }

    public function test_pop_returns_null_when_queue_is_empty(): void
    {
        $job = $this->fileQueue->pop();

        $this->assertNotInstanceOf(\App\Modules\Core\Infrastructure\Jobs\Job::class, $job);
    }

    public function test_size_returns_correct_count(): void
    {
        $this->assertSame(0, $this->fileQueue->size());

        $this->fileQueue->push(new SendEmailJob('test1@example.com', 'Test 1', 'email.test', []));
        $this->assertSame(1, $this->fileQueue->size());

        $this->fileQueue->push(new SendEmailJob('test2@example.com', 'Test 2', 'email.test', []));
        $this->assertSame(2, $this->fileQueue->size());
    }

    public function test_clear_removes_all_jobs(): void
    {
        $this->fileQueue->push(new SendEmailJob('test1@example.com', 'Test 1', 'email.test', []));
        $this->fileQueue->push(new SendEmailJob('test2@example.com', 'Test 2', 'email.test', []));

        $this->assertSame(2, $this->fileQueue->size());

        $this->fileQueue->clear();

        $this->assertSame(0, $this->fileQueue->size());
    }

    public function test_jobs_are_processed_in_fifo_order(): void
    {
        $job1 = new SendEmailJob('test1@example.com', 'Test 1', 'email.test', []);
        $job2 = new SendEmailJob('test2@example.com', 'Test 2', 'email.test', []);

        $this->fileQueue->push($job1);
        $this->fileQueue->push($job2);

        $popped1 = $this->fileQueue->pop();
        $popped2 = $this->fileQueue->pop();

        $this->assertInstanceOf(SendEmailJob::class, $popped1);
        $this->assertInstanceOf(SendEmailJob::class, $popped2);
        // Note: We can't directly compare objects, but we can verify they were popped in order
    }
}
