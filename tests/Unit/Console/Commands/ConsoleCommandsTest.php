<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CreateControllerCommand;
use App\Console\Commands\ListRoutesCommand;
use App\Console\Commands\MakeModelCommand;
use App\Console\Commands\PreviewEmailCommand;
use App\Console\Commands\QueueFailedCommand;
use App\Console\Commands\QueueFlushCommand;
use App\Console\Commands\QueueRetryCommand;
use App\Console\Commands\QueueStatsCommand;
use App\Console\Commands\QueueWorkCommand;
use App\Console\Commands\SeedDatabaseCommand;
use PHPUnit\Framework\TestCase;

class ConsoleCommandsTest extends TestCase
{
    public function test_create_controller_command_exists(): void
    {
        $this->assertTrue(class_exists(CreateControllerCommand::class));
    }

    public function test_list_routes_command_exists(): void
    {
        $this->assertTrue(class_exists(ListRoutesCommand::class));
    }

    public function test_make_model_command_exists(): void
    {
        $this->assertTrue(class_exists(MakeModelCommand::class));
    }

    public function test_preview_email_command_exists(): void
    {
        $this->assertTrue(class_exists(PreviewEmailCommand::class));
    }

    public function test_queue_failed_command_exists(): void
    {
        $this->assertTrue(class_exists(QueueFailedCommand::class));
    }

    public function test_queue_flush_command_exists(): void
    {
        $this->assertTrue(class_exists(QueueFlushCommand::class));
    }

    public function test_queue_retry_command_exists(): void
    {
        $this->assertTrue(class_exists(QueueRetryCommand::class));
    }

    public function test_queue_stats_command_exists(): void
    {
        $this->assertTrue(class_exists(QueueStatsCommand::class));
    }

    public function test_queue_work_command_exists(): void
    {
        $this->assertTrue(class_exists(QueueWorkCommand::class));
    }

    public function test_seed_database_command_exists(): void
    {
        $this->assertTrue(class_exists(SeedDatabaseCommand::class));
    }
}
