<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModelCommand extends Command
{
    protected static $defaultName = 'make:model';

    protected function configure(): void
    {
        $this->setDescription('Create a new model class, with optional migration.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the model')
            ->addOption('migration', 'm', InputOption::VALUE_NONE, 'Create a migration file for the model');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $modelName = ucfirst((string) $input->getArgument('name'));
        $createMigration = $input->getOption('migration');

        // Base project directory
        $projectRoot = dirname(__DIR__, 2);

        // Generate Model
        $this->createModel($modelName, $output, $projectRoot);

        // Generate Migration if -m is passed
        if ($createMigration) {
            $this->createMigration($modelName, $output);
        }

        $output->writeln('<info>Model created successfully!</info>');

        return Command::SUCCESS;
    }

    protected function createModel(string $modelName, OutputInterface $output, string $projectRoot): void
    {
        $modelDir = $projectRoot.'/Models/';
        $modelPath = $modelDir.$modelName.'.php';

        if (! is_dir($modelDir)) {
            mkdir($modelDir, 0777, true);
        }

        $projectRoot = dirname(__DIR__, 3);

        // Load stub and replace placeholder
        $stubPath = $projectRoot.'/stubs/model.stub';
        if (file_exists($stubPath)) {
            $stubContent = file_get_contents($stubPath);
            if ($stubContent === false) {
                $output->writeln(sprintf('<error>Failed to read stub: %s</error>', $stubPath));

                return;
            }

            $modelTemplate = str_replace('{{className}}', $modelName, $stubContent);
        } else {
            $output->writeln(sprintf('<error>Stub file not found: %s</error>', $stubPath));

            return;
        }

        file_put_contents($modelPath, $modelTemplate);
        $output->writeln('<info>Model created: </info>'.realpath($modelPath));
    }

    protected function createMigration(string $modelName, OutputInterface $output): void
    {
        $projectRoot = dirname(__DIR__, 3);

        $migrationDir = $projectRoot.'/database/migrations/';
        $className = 'Create'.ucfirst($modelName).'Table';
        $tableName = mb_strtolower($modelName).'s';
        $migrationPath = $migrationDir.$className.'.php';

        if (! is_dir($migrationDir)) {
            mkdir($migrationDir, 0777, true);
        }

        // Load stub and replace placeholders
        $stubPath = $projectRoot.'/stubs/migration.stub';
        if (file_exists($stubPath)) {
            $stubContent = file_get_contents($stubPath);
            if ($stubContent === false) {
                $output->writeln(sprintf('<error>Failed to read stub: %s</error>', $stubPath));

                return;
            }

            $migrationTemplate = str_replace(
                ['{{className}}', '{{tableName}}'],
                [$className, $tableName],
                $stubContent
            );
        } else {
            $output->writeln(sprintf('<error>Stub file not found: %s</error>', $stubPath));

            return;
        }

        file_put_contents($migrationPath, $migrationTemplate);
        $output->writeln('<info>Migration created: </info>'.realpath($migrationPath));
    }
}
