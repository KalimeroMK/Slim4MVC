<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\Queue\FailedJob;
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
            ->addOption('driver', null, InputOption::VALUE_OPTIONAL, 'Queue driver (file|redis)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver = $input->getOption('driver');
        $queue = $this->queueManager->queue($driver);

        $output->writeln('<info>Queue Statistics</info>');
        $output->writeln('==================');
        $output->writeln('');

        $size = $queue->size();
        $output->writeln(sprintf('Pending jobs: <comment>%d</comment>', $size));

        // Get failed jobs count
        $failedCount = FailedJob::count();
        $output->writeln(sprintf('Failed jobs: <comment>%s</comment>', $failedCount));

        // Get driver info
        $driverName = $driver ?? $_ENV['QUEUE_DRIVER'] ?? 'file';
        $output->writeln(sprintf('Driver: <comment>%s</comment>', $driverName));

        if ($queue instanceof RedisQueue) {
            $stats = $queue->stats();
            $output->writeln(sprintf('Queue name: <comment>%s</comment>', $stats['queue_name']));
        }

        return Command::SUCCESS;
    }
}
