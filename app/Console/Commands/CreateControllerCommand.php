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
                'stub' => $stubRoot.'/controller.stub',
                'destination' => sprintf('%s/app/Http/Controllers/%sController.php', $projectRoot, $name),
            ],

            // Actions
            [
                'stub' => $stubRoot.'/Actions/CreateAction',
                'destination' => sprintf('%s/app/Actions/%s/Create%sAction.php', $projectRoot, $name, $name),
            ],
            [
                'stub' => $stubRoot.'/Actions/DeleteAction',
                'destination' => sprintf('%s/app/Actions/%s/Delete%sAction.php', $projectRoot, $name, $name),
            ],
            [
                'stub' => $stubRoot.'/Actions/GetAction',
                'destination' => sprintf('%s/app/Actions/%s/Get%sAction.php', $projectRoot, $name, $name),
            ],
            [
                'stub' => $stubRoot.'/Actions/ListActon',
                'destination' => sprintf('%s/app/Actions/%s/List%sAction.php', $projectRoot, $name, $name),
            ],
            [
                'stub' => $stubRoot.'/Actions/UpdateAction',
                'destination' => sprintf('%s/app/Actions/%s/Update%sAction.php', $projectRoot, $name, $name),
            ],

            // DTOs
            [
                'stub' => $stubRoot.'/DTO/CreateDTO',
                'destination' => sprintf('%s/app/DTO/%s/Create%sDTO.php', $projectRoot, $name, $name),
            ],
            [
                'stub' => $stubRoot.'/DTO/UpdateDTO',
                'destination' => sprintf('%s/app/DTO/%s/Update%sDTO.php', $projectRoot, $name, $name),
            ],

            // Requests
            [
                'stub' => $stubRoot.'/Request/CreateRequest',
                'destination' => sprintf('%s/app/Http/Requests/%s/Create%sRequest.php', $projectRoot, $name, $name),
            ],
            [
                'stub' => $stubRoot.'/Request/UpdateRequest',
                'destination' => sprintf('%s/app/Http/Requests/%s/Update%sRequest.php', $projectRoot, $name, $name),
            ],
        ];

        foreach ($map as $item) {
            $stubPath = $item['stub'];
            $destination = $item['destination'];

            if (! file_exists($stubPath)) {
                $output->writeln(sprintf('<error>Missing stub: %s</error>', $stubPath));

                continue;
            }

            if (file_exists($destination)) {
                $output->writeln(sprintf('<comment>Skipped (already exists): %s</comment>', $destination));

                continue;
            }

            $dir = dirname($destination);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $stubContent = file_get_contents($stubPath);
            if ($stubContent === false) {
                $output->writeln(sprintf('<error>Failed to read stub: %s</error>', $stubPath));
                continue;
            }
            $content = str_replace('{{controllerName}}', $name, $stubContent);
            file_put_contents($destination, $content);

            $output->writeln(sprintf('<info>Created: %s</info>', $destination));
        }

        return Command::SUCCESS;
    }
}
