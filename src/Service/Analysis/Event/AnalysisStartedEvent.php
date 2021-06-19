<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\Event;

use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Model\Event\TimedTrait;

class AnalysisStartedEvent implements EventInterface
{
    use TimedTrait;

    public function __construct()
    {
        $this->microTime = microtime(true);
    }
}
