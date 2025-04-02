<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Factory;

return function ($container, Capsule $capsule): void {
    $filesystem = new Filesystem();
    $loader = new FileLoader($filesystem, __DIR__.'/../resources/lang');
    $translator = new Translator($loader, 'en');

    $validationFactory = new Factory($translator);

    $presenceVerifier = new DatabasePresenceVerifier($capsule->getDatabaseManager());
    $validationFactory->setPresenceVerifier($presenceVerifier);

    $container->set('validator', function () use ($validationFactory): Factory {
        return $validationFactory;
    });
};
