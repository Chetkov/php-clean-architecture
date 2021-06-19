<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Event;

trait TimedTrait
{
    /** @var float */
    private $microTime;

    /**
     * @return float
     */
    public function getMicroTime(): float
    {
        return $this->microTime;
    }
}
