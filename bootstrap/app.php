<?php

// bootstrap/app.php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Factory\AppFactory;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

// Initialize PHP-DI container
$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
$containerBuilder->addDefinitions(require __DIR__.'/../bootstrap/dependencies.php');
$container = $containerBuilder->build();

// Start session
$storage = new NativeSessionStorage();
$session = new Session($storage);
$session->start();
$container->set(Session::class, fn (): Session => $session);

// Configure database
$capsule = new Capsule;
require __DIR__.'/../bootstrap/database.php';

// Configure validation
$validation = require __DIR__.'/../bootstrap/validation.php';
$validation($container, $capsule);

// Configure Blade templating
(require __DIR__.'/../bootstrap/blade.php')($container);

// Set the container in Slim
AppFactory::setContainer($container);
$app = AppFactory::createFromContainer($container);

// Load middleware
(require __DIR__.'/../bootstrap/middleware.php')($app, $container);

// Load routes
(require __DIR__.'/../routes/web.php')($app);
(require __DIR__.'/../routes/api.php')($app);

$app->run();
