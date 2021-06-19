<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Infrastructure\Event;

use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Service\EventListenerInterface;
use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;

class EventManager implements EventManagerInterface
{
    /** @var array<EventListenerInterface> */
    private $listeners = [];

    /** @var array<EventInterface> */
    private $events = [];

    /**
     * @param array<EventListenerInterface> $listeners
     */
    public function __construct(array $listeners)
    {
        foreach ($listeners as $listener) {
            $this->subscribe($listener);
        }
    }

    /**
     * @param EventListenerInterface $listener
     */
    public function subscribe(EventListenerInterface $listener): void
    {
        $listenerClass = get_class($listener);
        if (!isset($this->listeners[$listenerClass])) {
            $this->listeners[$listenerClass] = $listener;
        }
    }

    /**
     * @param EventListenerInterface $listener
     */
    public function unsubscribe(EventListenerInterface $listener): void
    {
        $listenerClass = get_class($listener);
        unset($this->listeners[$listenerClass]);
    }

    /**
     * @param EventInterface $event
     * @param bool $releaseNow
     */
    public function notify(EventInterface $event, bool $releaseNow = true): void
    {
        if ($releaseNow) {
            foreach ($this->listeners as $listener) {
                $listener->handle($event);
            }
        } else {
            $eventHash = spl_object_hash($event);
            if (!isset($this->events[$eventHash])) {
                $this->events[$eventHash] = $event;
            }
        }
    }

    public function releaseAll(): void
    {
        foreach ($this->events as $event) {
            $this->notify($event);
        }
    }
}
