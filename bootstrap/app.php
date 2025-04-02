<?php

declare (strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Csrf\Guard;
use Slim\Factory\AppFactory;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Start session.
 */
$session = new Session();
$session->start();

$container = new Container;
$container->set('session', $session);

// Create an instance of Capsule and load the database configuration
$capsule = new Capsule;
require __DIR__ . '/../bootstrap/database.php';

// Load the validation configuration
$validation = require __DIR__ . '/../bootstrap/validation.php';
$validation($container, $capsule);

// Set the container in AppFactory
AppFactory::setContainer($container);

// Create Slim App instance
$app = AppFactory::create();
$responseFactory = $app->getResponseFactory();
$container->set('csrf', function () use ($responseFactory) {
    return new Guard($responseFactory);
});
// Add routes and other services
(require __DIR__ . '/../routes/web.php')($app);
(require __DIR__ . '/../routes/api.php')($app);

$app->run();
