<?php

declare(strict_types=1);

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
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

    // Register the translator
    $container->set(TranslatorContract::class, function () use ($translator): TranslatorContract {
        return $translator;
    });

    $validationFactory = new Factory($translator);

    $presenceVerifier = new DatabasePresenceVerifier($capsule->getDatabaseManager());
    $validationFactory->setPresenceVerifier($presenceVerifier);

    // Register the validation factory
    $container->set(Factory::class, function () use ($validationFactory): Factory {
        return $validationFactory;
    });
};
