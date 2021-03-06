<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

/**
 * Class CompositeDependenciesFinder
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder
 */
class CompositeDependenciesFinder implements DependenciesFinderInterface
{
    /** @var array<DependenciesFinderInterface> */
    private $strategies;

    /**
     * @param DependenciesFinderInterface ...$strategies
     */
    public function __construct(DependenciesFinderInterface ...$strategies)
    {
        $this->strategies = $strategies;
    }

    /**
     * @inheritDoc
     */
    public function find(UnitOfCode $unitOfCode): array
    {
        $dependencies = [];
        foreach ($this->strategies as $strategy) {
            $dependencies[] = $strategy->find($unitOfCode);
        }
        return array_unique(array_merge(...$dependencies));
    }
}
