<?php

use Symfony\Component\Console\Application;
use App\Console\Commands\MakeModelCommand;

// Autoload dependencies
require __DIR__ . '/vendor/autoload.php';

// Create console application
$application = new Application();

// Register the command
$application->add(new MakeModelCommand());

// Run the console application
$application->run();
