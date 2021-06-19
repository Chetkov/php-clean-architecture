<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

/**
 * Interface DependenciesFinderInterface
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder
 */
interface DependenciesFinderInterface
{
    /**
     * @param UnitOfCode $unitOfCode
     * @return array<string>
     */
    public function find(UnitOfCode $unitOfCode): array;
}
