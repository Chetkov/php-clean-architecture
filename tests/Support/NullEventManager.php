<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Support;

use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Service\EventListenerInterface;
use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;

final class NullEventManager implements EventManagerInterface
{
    public function subscribe(EventListenerInterface $listener): void
    {
    }

    public function unsubscribe(EventListenerInterface $listener): void
    {
    }

    public function notify(EventInterface $event, bool $releaseNow = true): void
    {
    }

    public function releaseAll(): void
    {
    }
}
