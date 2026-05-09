<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\Event;

use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Model\Event\ProgressiveTrait;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

final class UnitOfCodeReportRenderedEvent implements EventInterface
{
    use ProgressiveTrait;

    /** @var UnitOfCode */
    private $unitOfCode;

    public function __construct(int $position, int $totalPositions, UnitOfCode $unitOfCode)
    {
        $this->position = $position;
        $this->totalPositions = $totalPositions;
        $this->unitOfCode = $unitOfCode;
    }

    public function getUnitOfCode(): UnitOfCode
    {
        return $this->unitOfCode;
    }
}
