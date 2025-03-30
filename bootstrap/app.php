<?php

declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\Session;

require __DIR__.'/../vendor/autoload.php';

$container = new Container;

$settings = require __DIR__.'/../bootstrap/settings.php';
$settings($container);

AppFactory::setContainer($container);

$app = AppFactory::create();

$app->add(
    new Session([
        'name' => 'dummy_session',
        'autorefresh' => true,
        'lifetime' => '1 hour',
    ])
);
(require __DIR__.'/../bootstrap/database.php');
(require __DIR__.'/../routes/web.php')($app);
(require __DIR__.'/../routes/api.php')($app);

$app->run();
