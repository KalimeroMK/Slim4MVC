<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\Jobs\Job;
use App\Modules\Core\Infrastructure\Queue\FailedJob;
use App\Modules\Core\Infrastructure\Queue\Queue;
use Exception;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionNamedType;
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
                $reflection = new ReflectionMethod($job, 'handle');
                $params = $reflection->getParameters();

                if (count($params) > 0) {
                    $paramType = $params[0]->getType();
                    if ($paramType instanceof ReflectionNamedType && $paramType->getName() === ContainerInterface::class) {
                        /** @phpstan-ignore-next-line */
                        $job->handle($this->container);
                    } else {
                        $job->handle();
                    }
                } else {
                    $job->handle();
                }

                ++$processed;
                $output->writeln(sprintf('<info>✓ Processed job #%d</info>', $processed));

                if ($maxJobs > 0 && $processed >= $maxJobs) {
                    $output->writeln(sprintf('<info>Processed %d jobs. Stopping.</info>', $processed));

                    break;
                }
            } catch (Exception $e) {
                ++$failed;
                $attempts = $job->attempts();
                $shouldRetry = $job->shouldRetry();

                if ($shouldRetry) {
                    // Retry the job
                    $this->queue->push($job);
                    $output->writeln(sprintf('<comment>⚠ Retrying job (attempt %d/%d): %s</comment>', $attempts, $maxTries, $e->getMessage()));
                } else {
                    // Store as failed job
                    FailedJob::store($job, $e, $attempts);
                    $output->writeln(sprintf('<error>✗ Failed job stored (attempt %d/%d): %s</error>', $attempts, $maxTries, $e->getMessage()));
                }
            }
        }

        $output->writeln(sprintf('<info>Queue worker stopped. Processed: %d, Failed: %d</info>', $processed, $failed));

        return Command::SUCCESS;
    }
}
