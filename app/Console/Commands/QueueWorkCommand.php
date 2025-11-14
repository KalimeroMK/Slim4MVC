<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\Jobs\Job;
use App\Modules\Core\Infrastructure\Queue\FailedJob;
use App\Modules\Core\Infrastructure\Queue\Queue;
use Exception;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueWorkCommand extends Command
{
    protected static $defaultName = 'queue:work';

    public function __construct(
        private readonly Queue $queue,
        private readonly ContainerInterface $container
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Process jobs from the queue')
            ->addOption('stop-when-empty', null, InputOption::VALUE_NONE, 'Stop when queue is empty')
            ->addOption('max-jobs', null, InputOption::VALUE_OPTIONAL, 'Maximum number of jobs to process', '0')
            ->addOption('tries', null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before marking it as failed', '3');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopWhenEmpty = $input->getOption('stop-when-empty');
        $maxJobs = (int) $input->getOption('max-jobs');
        $maxTries = (int) $input->getOption('tries');
        $processed = 0;
        $failed = 0;

        $output->writeln('<info>Queue worker started...</info>');

        while (true) {
            $job = $this->queue->pop();

            if (! $job instanceof Job) {
                if ($stopWhenEmpty) {
                    $output->writeln('<info>Queue is empty. Stopping.</info>');

                    break;
                }

                sleep(1); // Wait 1 second before checking again

                continue;
            }

            try {
                // Set max attempts if job supports it
                if (method_exists($job, 'setMaxAttempts')) {
                    $job->setMaxAttempts($maxTries);
                }

                // Increment attempts if job supports it
                if (method_exists($job, 'incrementAttempts')) {
                    $job->incrementAttempts();
                }

                // Pass container to job if it accepts it
                if (method_exists($job, 'handle')) {
                    $reflection = new ReflectionMethod($job, 'handle');
                    $params = $reflection->getParameters();

                    if (count($params) > 0 && $params[0]->getType()?->getName() === 'Psr\Container\ContainerInterface') {
                        $job->handle($this->container);
                    } else {
                        $job->handle();
                    }
                }

                $processed++;
                $output->writeln("<info>✓ Processed job #{$processed}</info>");

                if ($maxJobs > 0 && $processed >= $maxJobs) {
                    $output->writeln("<info>Processed {$processed} jobs. Stopping.</info>");

                    break;
                }
            } catch (Exception $e) {
                $failed++;
                $attempts = method_exists($job, 'attempts') ? $job->attempts() : 1;
                $shouldRetry = method_exists($job, 'shouldRetry') && $job->shouldRetry();

                if ($shouldRetry) {
                    // Retry the job
                    $this->queue->push($job);
                    $output->writeln("<comment>⚠ Retrying job (attempt {$attempts}/{$maxTries}): {$e->getMessage()}</comment>");
                } else {
                    // Store as failed job
                    FailedJob::store($job, $e, $attempts);
                    $output->writeln("<error>✗ Failed job stored (attempt {$attempts}/{$maxTries}): {$e->getMessage()}</error>");
                }
            }
        }

        $output->writeln("<info>Queue worker stopped. Processed: {$processed}, Failed: {$failed}</info>");

        return Command::SUCCESS;
    }
}
