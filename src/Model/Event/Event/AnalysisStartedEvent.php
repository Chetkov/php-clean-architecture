<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Event\Event;

use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;

class AnalysisStartedEvent implements EventInterface
{
    use TimedTrait;

    public function __construct()
    {
        $this->microTime = microtime(true);
    }
}
