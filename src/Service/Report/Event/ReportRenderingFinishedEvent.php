<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\Event;

use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Model\Event\TimedTrait;

final class ReportRenderingFinishedEvent implements EventInterface
{
    use TimedTrait;

    public function __construct()
    {
        $this->microTime = microtime(true);
    }
}
