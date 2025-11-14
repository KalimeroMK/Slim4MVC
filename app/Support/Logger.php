<?php

declare(strict_types=1);

namespace App\Support;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Log\LoggerInterface;

class Logger
{
    private static ?Container $container = null;

    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $logger = self::getLogger();
        if ($logger instanceof \Psr\Log\LoggerInterface) {
            $logger->log($level, $message, $context);
        }
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    private static function getLogger(): ?LoggerInterface
    {
        if (!self::$container instanceof \DI\Container) {
            return null;
        }

        try {
            return self::$container->get(LoggerInterface::class);
        } catch (DependencyException|NotFoundException $e) {
            return null;
        }
    }
}
