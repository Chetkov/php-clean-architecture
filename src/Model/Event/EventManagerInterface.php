<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Event;

interface EventManagerInterface
{
    public function subscribe(EventListenerInterface $listener): void;

    public function unsubscribe(EventListenerInterface $listener): void;

    public function notify(EventInterface $event, bool $releaseNow = true): void;

    public function releaseAll(): void;
}
