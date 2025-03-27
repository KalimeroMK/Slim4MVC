<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

(require __DIR__.'/../routes/web.php')($app);
(require __DIR__.'/../routes/api.php')($app);


$app->run();
