<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service;

use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;

interface EventListenerInterface
{
    public function handle(EventInterface $event): void;
}
