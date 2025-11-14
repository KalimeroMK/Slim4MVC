<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\Queue\FailedJob;
use App\Modules\Core\Infrastructure\Queue\Queue;
use App\Modules\Core\Infrastructure\Queue\QueueManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueRetryCommand extends Command
{
    protected static $defaultName = 'queue:retry';

    public function __construct(
        private readonly QueueManager $queueManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Retry a failed job')
            ->addArgument('id', InputArgument::OPTIONAL, 'Failed job ID to retry')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Retry all failed jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $retryAll = $input->getOption('all');

        if ($retryAll) {
            return $this->retryAll($output);
        }

        if ($id === null) {
            $output->writeln('<error>Please provide a job ID or use --all option</error>');

            return Command::FAILURE;
        }

        return $this->retryJob((int) $id, $output);
    }

    private function retryJob(int $id, OutputInterface $output): int
    {
        $failedJob = FailedJob::find($id);

        if ($failedJob === null) {
            $output->writeln("<error>Failed job with ID {$id} not found</error>");

            return Command::FAILURE;
        }

        $queue = $this->queueManager->queue();

        if ($failedJob->retry($queue)) {
            $output->writeln("<info>✓ Retried failed job #{$id}</info>");

            return Command::SUCCESS;
        }

        $output->writeln("<error>✗ Failed to retry job #{$id}</error>");

        return Command::FAILURE;
    }

    private function retryAll(OutputInterface $output): int
    {
        $failedJobs = FailedJob::all();
        $queue = $this->queueManager->queue();
        $retried = 0;
        $failed = 0;

        foreach ($failedJobs as $failedJob) {
            if ($failedJob->retry($queue)) {
                $retried++;
            } else {
                $failed++;
            }
        }

        $output->writeln("<info>✓ Retried {$retried} jobs</info>");

        if ($failed > 0) {
            $output->writeln("<error>✗ Failed to retry {$failed} jobs</error>");
        }

        return Command::SUCCESS;
    }
}

