<?php

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
     * @return string[]
     */
    public function find(UnitOfCode $unitOfCode): array;
}
