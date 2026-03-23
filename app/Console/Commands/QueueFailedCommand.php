<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\Queue\FailedJob;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueueFailedCommand extends Command
{
    protected static $defaultName = 'queue:failed';

    protected function configure(): void
    {
        $this->setDescription('List all failed jobs')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number of results', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');

        $failedJobs = FailedJob::orderBy('failed_at', 'desc')
            ->limit($limit)
            ->get();

        if ($failedJobs->isEmpty()) {
            $symfonyStyle->success('No failed jobs found.');

            return Command::SUCCESS;
        }

        $symfonyStyle->title('Failed Jobs');

        $rows = [];
        foreach ($failedJobs as $failedJob) {
            $rows[] = [
                $failedJob->id,
                $failedJob->job_class,
                $failedJob->exception,
                $failedJob->exception_message,
                date('Y-m-d H:i:s', $failedJob->failed_at),
                $failedJob->attempts,
            ];
        }

        $symfonyStyle->table(
            ['ID', 'Job Class', 'Exception', 'Message', 'Failed At', 'Attempts'],
            $rows
        );

        $total = FailedJob::count();
        $symfonyStyle->note('Total failed jobs: '.$total);

        return Command::SUCCESS;
    }
}
