<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Event;

trait ProgressiveTrait
{
    /** @var int */
    private $position;

    /** @var int */
    private $totalPositions;

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return mixed
     */
    public function getTotalPositions(): int
    {
        return $this->totalPositions;
    }
}
