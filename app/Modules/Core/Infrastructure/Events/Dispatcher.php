<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Events;

use Psr\Container\ContainerInterface;

class Dispatcher
{
    /** @var array<string, list<string|callable>> */
    private array $listeners = [];

    public function __construct(
        private readonly ContainerInterface $container
    ) {}

    /**
     * Register an event listener.
     *
     * @param  string  $event  Event class name
     * @param  class-string|callable  $listener  Listener class name or callable
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
        $eventClass = $event::class;

        if (! isset($this->listeners[$eventClass])) {
            return;
        }

        foreach ($this->listeners[$eventClass] as $listener) {
            if (is_string($listener)) {
                $listenerInstance = $this->container->get($listener);
                if (method_exists($listenerInstance, 'handle')) {
                    /** @phpstan-ignore-next-line */
                    $listenerInstance->handle($event);
                }
            } elseif (is_callable($listener)) {
                $listener($event);
            }
        }
    }

    /**
     * Get all registered listeners.
     *
     * @return array<string, list<string|callable>>
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }
}
