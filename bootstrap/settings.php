<?php


return function ($container) {
    $container->set('settings', function () {
        return [
            'view.paths' => [__DIR__ . '/../resources/views'],
            'view.compiled' => __DIR__ . '/../storage/cache',
        ];
    });
};