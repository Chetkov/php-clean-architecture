<?php

namespace Chetkov\PHPCleanArchitecture\Service\DependenciesFinder;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

/**
 * Interface DependenciesFinderInterface
 * @package Chetkov\PHPCleanArchitecture\Service\DependenciesFinder
 */
interface DependenciesFinderInterface
{
    /**
     * @param UnitOfCode $unitOfCode
     * @return string[]
     */
    public function find(UnitOfCode $unitOfCode): array;
}
