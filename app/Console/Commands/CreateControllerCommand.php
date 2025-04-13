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
        $this->setDescription('Creates a controller and associated stubs (Actions, DTOs, Requests).')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the controller (e.g., Role)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $projectRoot = dirname(__DIR__, 3);
        $stubRoot = $projectRoot.'/stubs';

        $map = [
            // Controllers
            [
                'stub' => "$stubRoot/controller.stub",
                'destination' => "$projectRoot/app/Http/Controllers/{$name}Controller.php",
            ],

            // Actions
            [
                'stub' => "$stubRoot/Actions/CreateAction",
                'destination' => "$projectRoot/app/Actions/$name/Create{$name}Action.php",
            ],
            [
                'stub' => "$stubRoot/Actions/DeleteAction",
                'destination' => "$projectRoot/app/Actions/$name/Delete{$name}Action.php",
            ],
            [
                'stub' => "$stubRoot/Actions/GetAction",
                'destination' => "$projectRoot/app/Actions/$name/Get{$name}Action.php",
            ],
            [
                'stub' => "$stubRoot/Actions/ListActon",
                'destination' => "$projectRoot/app/Actions/$name/List{$name}Action.php",
            ],
            [
                'stub' => "$stubRoot/Actions/UpdateAction",
                'destination' => "$projectRoot/app/Actions/$name/Update{$name}Action.php",
            ],

            // DTOs
            [
                'stub' => "$stubRoot/DTO/CreateDTO",
                'destination' => "$projectRoot/app/DTO/$name/Create{$name}DTO.php",
            ],
            [
                'stub' => "$stubRoot/DTO/UpdateDTO",
                'destination' => "$projectRoot/app/DTO/$name/Update{$name}DTO.php",
            ],

            // Requests
            [
                'stub' => "$stubRoot/Request/CreateRequest",
                'destination' => "$projectRoot/app/Http/Requests/$name/Create{$name}Request.php",
            ],
            [
                'stub' => "$stubRoot/Request/UpdateRequest",
                'destination' => "$projectRoot/app/Http/Requests/$name/Update{$name}Request.php",
            ],
        ];

        foreach ($map as $item) {
            $stubPath = $item['stub'];
            $destination = $item['destination'];

            if (! file_exists($stubPath)) {
                $output->writeln("<error>Missing stub: $stubPath</error>");

                continue;
            }

            if (file_exists($destination)) {
                $output->writeln("<comment>Skipped (already exists): $destination</comment>");

                continue;
            }

            $dir = dirname($destination);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $content = str_replace('{{controllerName}}', $name, file_get_contents($stubPath));
            file_put_contents($destination, $content);

            $output->writeln("<info>Created: $destination</info>");
        }

        return Command::SUCCESS;
    }
}
