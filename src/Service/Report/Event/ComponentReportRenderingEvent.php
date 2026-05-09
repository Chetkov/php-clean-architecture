<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\Event;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Model\Event\ProgressiveTrait;
use Chetkov\PHPCleanArchitecture\Model\Event\TimedTrait;

abstract class ComponentReportRenderingEvent implements EventInterface
{
    use TimedTrait;
    use ProgressiveTrait;

    /** @var Component */
    private $component;

    public function __construct(int $position, int $totalPositions, Component $component)
    {
        $this->position = $position;
        $this->totalPositions = $totalPositions;
        $this->component = $component;
        $this->microTime = microtime(true);
    }

    public function getComponent(): Component
    {
        return $this->component;
    }
}
