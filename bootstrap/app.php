<?php

// bootstrap/app.php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Factory\AppFactory;

// Initialize container
$container = require __DIR__ . '/container.php';

// Configure database
$capsule = new Capsule;
require __DIR__.'/database.php';

// Configure validation
$validation = require __DIR__.'/validation.php';
$validation($container, $capsule);

// Configure Blade templating
(require __DIR__.'/blade.php')($container);

// Set the container in Slim
AppFactory::setContainer($container);
$app = AppFactory::createFromContainer($container);

// Load middleware
(require __DIR__.'/middleware.php')($app, $container);

// Load routes
(require __DIR__.'/../routes/web.php')($app);
(require __DIR__.'/../routes/api.php')($app);

$app->run();
