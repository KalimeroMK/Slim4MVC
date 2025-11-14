<?php

declare(strict_types=1);

namespace App\Events;

use Psr\Container\ContainerInterface;

class Dispatcher
{
    /**
     * @var array<string, array<string>>
     */
    private array $listeners = [];

    public function __construct(
        private readonly ContainerInterface $container
    ) {}

    /**
     * Register an event listener.
     *
     * @param  string  $event  Event class name
     * @param  string|callable  $listener  Listener class name or callable
     */
    public function listen(string $event, string|callable $listener): void
    {
        if (! isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispatch an event.
     */
    public function dispatch(Event $event): void
    {
        $eventClass = get_class($event);

        if (! isset($this->listeners[$eventClass])) {
            return;
        }

        foreach ($this->listeners[$eventClass] as $listener) {
            if (is_callable($listener)) {
                $listener($event);
            } elseif (is_string($listener)) {
                $listenerInstance = $this->container->get($listener);
                if (method_exists($listenerInstance, 'handle')) {
                    $listenerInstance->handle($event);
                }
            }
        }
    }

    /**
     * Get all registered listeners.
     *
     * @return array<string, array<string>>
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }
}
