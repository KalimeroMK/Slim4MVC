<?php

declare(strict_types=1);

use App\Events\Dispatcher;
use App\Events\PasswordResetRequested;
use App\Events\UserRegistered;
use App\Listeners\SendPasswordResetEmail;
use App\Listeners\SendWelcomeEmail;
use Psr\Container\ContainerInterface;

return function (ContainerInterface $container): void {
    $dispatcher = $container->get(Dispatcher::class);

    // Register event listeners
    $dispatcher->listen(UserRegistered::class, SendWelcomeEmail::class);
    $dispatcher->listen(PasswordResetRequested::class, SendPasswordResetEmail::class);

    // Store dispatcher in container for easy access
    $container->set(Dispatcher::class, $dispatcher);
};
