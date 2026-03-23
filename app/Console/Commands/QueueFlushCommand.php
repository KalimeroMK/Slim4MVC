<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\Queue\FailedJob;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class QueueFlushCommand extends Command
{
    protected static $defaultName = 'queue:flush';

    protected function configure(): void
    {
        $this->setDescription('Flush all failed jobs')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force the operation without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');

        if (! $force) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $confirmationQuestion = new ConfirmationQuestion(
                'Are you sure you want to flush all failed jobs? (yes/no) ',
                false
            );

            if (! $helper->ask($input, $output, $confirmationQuestion)) {
                $output->writeln('<info>Operation cancelled.</info>');

                return Command::SUCCESS;
            }
        }

        $count = FailedJob::count();
        FailedJob::truncate();

        $output->writeln(sprintf('<info>✓ Flushed %s failed jobs</info>', $count));

        return Command::SUCCESS;
    }
}
