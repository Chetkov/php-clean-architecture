<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Event\Event;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;

abstract class ComponentAnalysisEvent implements EventInterface
{
    use TimedTrait, ProgressiveTrait;

    /** @var Component */
    private $component;

    /**
     * @param int $position
     * @param int $totalPositions
     * @param Component $component
     */
    public function __construct(int $position, int $totalPositions, Component $component)
    {
        $this->position = $position;
        $this->totalPositions = $totalPositions;
        $this->component = $component;
        $this->microTime = microtime(true);
    }

    /**
     * @return Component
     */
    public function getComponent(): Component
    {
        return $this->component;
    }
}
