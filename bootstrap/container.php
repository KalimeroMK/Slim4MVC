<?php

// bootstrap/container.php

declare(strict_types=1);

use DI\ContainerBuilder;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

require_once __DIR__.'/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
$containerBuilder->addDefinitions(require __DIR__.'/dependencies.php');

$container = $containerBuilder->build();

// Start and bind session
$storage = new NativeSessionStorage();
$session = new Session($storage);
$session->start();
$container->set(Session::class, fn (): Session => $session);

return $container;
