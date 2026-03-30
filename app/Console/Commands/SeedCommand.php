<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seed\DatabaseSeeder;

class SeedCommand
{
    public function execute(): void
    {
        $databaseSeeder = new DatabaseSeeder();
        $databaseSeeder->run();
    }
}
