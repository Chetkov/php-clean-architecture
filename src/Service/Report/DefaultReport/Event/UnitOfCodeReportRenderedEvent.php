<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event;

use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Model\Event\ProgressiveTrait;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

class UnitOfCodeReportRenderedEvent implements EventInterface
{
    use ProgressiveTrait;

    /** @var UnitOfCode */
    private $unitOfCOde;

    /**
     * @param int $position
     * @param int $totalPositions
     * @param UnitOfCode $unitOfCode
     */
    public function __construct(
        int $position,
        int $totalPositions,
        UnitOfCode $unitOfCode
    ) {
        $this->position = $position;
        $this->totalPositions = $totalPositions;
        $this->unitOfCOde = $unitOfCode;
    }

    /**
     * @return UnitOfCode
     */
    public function getUnitOfCode(): UnitOfCode
    {
        return $this->unitOfCOde;
    }
}
