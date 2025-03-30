<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use DI\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Factory\AppFactory;

$container = new Container();

// Load the settings (if it's a callable)
$settings = require __DIR__.'/../bootstrap/settings.php';
if (is_callable($settings)) {
    $settings($container);
}

// Create an instance of Capsule and load the database configuration
$capsule = new Capsule;
require __DIR__.'/../bootstrap/database.php';

// Load the validation configuration
$validation = require __DIR__.'/../bootstrap/validation.php';
$validation($container, $capsule);

// Set the container in AppFactory
AppFactory::setContainer($container);

// Create Slim App instance
$app = AppFactory::create();

// Add routes and other services
(require __DIR__.'/../routes/web.php')($app);
(require __DIR__.'/../routes/api.php')($app);

$app->run();
