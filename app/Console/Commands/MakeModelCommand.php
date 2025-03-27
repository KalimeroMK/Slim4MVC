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
        $modelName = ucfirst($input->getArgument('name'));
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
            $modelTemplate = file_get_contents($stubPath);
            $modelTemplate = str_replace('{{className}}', $modelName, $modelTemplate);
        } else {
            $output->writeln("<error>Stub file not found: $stubPath</error>");

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
            $migrationTemplate = file_get_contents($stubPath);
            $migrationTemplate = str_replace(
                ['{{className}}', '{{tableName}}'],
                [$className, $tableName],
                $migrationTemplate
            );
        } else {
            $output->writeln("<error>Stub file not found: $stubPath</error>");

            return;
        }

        file_put_contents($migrationPath, $migrationTemplate);
        $output->writeln('<info>Migration created: </info>'.realpath($migrationPath));
    }
}
