<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Event;

interface EventListenerInterface
{
    public function handle(EventInterface $event): void;
}
