<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\Support\Paths;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeedDatabaseCommand extends Command
{
    protected static $defaultName = 'db:seed';

    protected function configure(): void
    {
        $this->setDescription('Seed the database with initial data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Load the DB
        require Paths::root().'/bootstrap/database.php';

        $output->writeln('<info>Seeding database...</info>');

        // You can also split into separate classes/files
        $this->seed();

        $output->writeln('<info>✔ Done.</info>');

        return Command::SUCCESS;
    }

    private function seed(): void
    {
        // Your seeding logic here
        // You can also include the logic from database/seed/seed.php
        require Paths::root().'/database/seed/seed.php';
    }
}
