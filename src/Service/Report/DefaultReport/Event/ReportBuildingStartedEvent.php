<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event;

use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Model\Event\TimedTrait;

class ReportBuildingStartedEvent implements EventInterface
{
    use TimedTrait;

    public function __construct()
    {
        $this->microTime = microtime(true);
    }
}
