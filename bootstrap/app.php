<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Support\Blade;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container;

$container->set(Blade::class, function () {
    $views = __DIR__ . '/../resources/views';
    $cache = __DIR__ . '/../storage/cache/';
    return new Blade($views, $cache);
});
$settings = require __DIR__ . '/../bootstrap/settings.php';
$settings($container);

AppFactory::setContainer($container);

$app = AppFactory::create();

(require __DIR__.'/../bootstrap/database.php');
(require __DIR__.'/../routes/web.php')($app);
(require __DIR__.'/../routes/api.php')($app);

$app->run();