<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateControllerCommand extends Command
{
    protected static $defaultName = 'make:controller';

    protected function configure(): void
    {
        $this->setDescription('Creates a new controller.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the controller');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get controller name from the argument
        $controllerName = $input->getArgument('name');

        // Path to the stub file
        $projectRoot = dirname(__DIR__, 3);

        // Load stub and replace placeholder
        $stubPath = $projectRoot.'/stubs/controller.stub';

        // Ensure the stub file exists
        if (! file_exists($stubPath)) {
            $output->writeln('<error>Stub file not found!</error>');

            return Command::FAILURE;
        }

        // Generate the controller content from the stub
        $controllerContent = $this->generateControllerContent($controllerName, $stubPath);

        // Define the file path for the controller
        $projectRoot = dirname(__DIR__, 3);

        $filePath = $projectRoot.'/app/Http/Controllers/'.$controllerName.'Controller.php';

        // Check if the controller already exists
        if (file_exists($filePath)) {
            $output->writeln('<error>Controller already exists!</error>');

            return Command::FAILURE;
        }

        // Create the controller file
        file_put_contents($filePath, $controllerContent);

        $output->writeln("<info>Controller $controllerName created successfully!</info>");

        return Command::SUCCESS;
    }

    // Generate the controller content from the stub
    private function generateControllerContent(string $controllerName, string $stubPath): string
    {
        // Read the stub file
        $stub = file_get_contents($stubPath);

        // Replace the placeholder with the controller name
        return str_replace('{{controllerName}}', $controllerName, $stub);
    }
}
