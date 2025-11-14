<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\Queue\FailedJob;
use App\Modules\Core\Infrastructure\Queue\Queue;
use App\Modules\Core\Infrastructure\Queue\QueueManager;
use App\Modules\Core\Infrastructure\Queue\RedisQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueStatsCommand extends Command
{
    protected static $defaultName = 'queue:stats';

    public function __construct(
        private readonly QueueManager $queueManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Display queue statistics')
            ->addOption('driver', null, InputOption::VALUE_OPTIONAL, 'Queue driver (file|redis)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver = $input->getOption('driver');
        $queue = $this->queueManager->queue($driver);

        $output->writeln('<info>Queue Statistics</info>');
        $output->writeln('==================');
        $output->writeln('');

        $size = $queue->size();
        $output->writeln("Pending jobs: <comment>{$size}</comment>");

        // Get failed jobs count
        $failedCount = FailedJob::count();
        $output->writeln("Failed jobs: <comment>{$failedCount}</comment>");

        // Get driver info
        $driverName = $driver ?? $_ENV['QUEUE_DRIVER'] ?? 'file';
        $output->writeln("Driver: <comment>{$driverName}</comment>");

        if ($queue instanceof RedisQueue) {
            $stats = $queue->stats();
            $output->writeln("Queue name: <comment>{$stats['queue_name']}</comment>");
        }

        return Command::SUCCESS;
    }
}

