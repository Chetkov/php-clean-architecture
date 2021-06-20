<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\Event;

use Chetkov\PHPCleanArchitecture\Model\ComponentInterface;
use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Model\Event\ProgressiveTrait;
use Chetkov\PHPCleanArchitecture\Model\Event\TimedTrait;

abstract class ComponentAnalysisEvent implements EventInterface
{
    use TimedTrait, ProgressiveTrait;

    /** @var ComponentInterface */
    private $component;

    /**
     * @param int $position
     * @param int $totalPositions
     * @param ComponentInterface $component
     */
    public function __construct(int $position, int $totalPositions, ComponentInterface $component)
    {
        $this->position = $position;
        $this->totalPositions = $totalPositions;
        $this->component = $component;
        $this->microTime = microtime(true);
    }

    /**
     * @return ComponentInterface
     */
    public function getComponent(): ComponentInterface
    {
        return $this->component;
    }
}
