<?php

declare(strict_types=1);

use App\Console\Commands\MakeModelCommand;
use Symfony\Component\Console\Application;

// Autoload dependencies
require __DIR__.'/vendor/autoload.php';

// Create console application
$application = new Application;

// Register the command
$application->add(new MakeModelCommand);

// Run the console application
$application->run();
