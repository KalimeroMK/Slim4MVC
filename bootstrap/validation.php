<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Database\Capsule\Manager as Capsule;

return function ($container, Capsule $capsule) {
    $filesystem = new Filesystem();
    $loader = new FileLoader($filesystem, __DIR__ . '/../resources/lang');
    $translator = new Translator($loader, 'en');

    $validationFactory = new Factory($translator);

    $presenceVerifier = new DatabasePresenceVerifier($capsule->getDatabaseManager());
    $validationFactory->setPresenceVerifier($presenceVerifier);

    $container->set('validator', function () use ($validationFactory) {
        return $validationFactory;
    });
};
