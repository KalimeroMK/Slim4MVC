<?php

declare(strict_types=1);

return function ($container): void {
    $container->set('settings', function (): array {
        return [
            'view.paths' => [__DIR__.'/../resources/views'],
            'view.compiled' => __DIR__.'/../storage/cache',
        ];
    });
};
