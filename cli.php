<?php

declare(strict_types=1);

use App\Console\Commands\ListRoutesCommand;
use Slim\Factory\AppFactory;
use Symfony\Component\Console\Application;

// Autoload dependencies
require __DIR__ . '/vendor/autoload.php';

// Create Slim app
$app = AppFactory::create();

// Load routes from routes/web.php
(require __DIR__ . '/routes/web.php')($app); // Execute the closure and pass the app

// Create console application
$application = new Application();

// Register the ListRoutesCommand and pass $app to it
$application->add(new ListRoutesCommand($app));

// Run the console application
$application->run();
