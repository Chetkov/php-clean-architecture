<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Support;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\DependenciesFinderInterface;

final class CapturingDependenciesFinder implements DependenciesFinderInterface
{
    /** @var array<UnitOfCode> */
    private $unitsOfCode = [];

    /**
     * @inheritDoc
     */
    public function find(UnitOfCode $unitOfCode): array
    {
        $this->unitsOfCode[] = $unitOfCode;
        return [];
    }

    /**
     * @return array<UnitOfCode>
     */
    public function unitsOfCode(): array
    {
        return $this->unitsOfCode;
    }
}
