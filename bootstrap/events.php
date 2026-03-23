<?php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\Events\Dispatcher;
use App\Modules\Core\Infrastructure\Events\PasswordResetRequested;
use App\Modules\Core\Infrastructure\Events\UserRegistered;
use App\Modules\Core\Infrastructure\Listeners\SendPasswordResetEmail;
use App\Modules\Core\Infrastructure\Listeners\SendWelcomeEmail;
use DI\Container;

return function (Container $container): void {
    $dispatcher = $container->get(Dispatcher::class);

    // Register event listeners
    $dispatcher->listen(UserRegistered::class, SendWelcomeEmail::class);
    $dispatcher->listen(PasswordResetRequested::class, SendPasswordResetEmail::class);

    // Store dispatcher in container for easy access
    $container->set(Dispatcher::class, $dispatcher);
};
