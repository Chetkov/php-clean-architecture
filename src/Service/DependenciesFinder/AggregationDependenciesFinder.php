<?php

namespace Chetkov\PHPCleanArchitecture\Service\DependenciesFinder;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

/**
 * Class AggregationDependenciesFinder
 * @package Chetkov\PHPCleanArchitecture\Service\DependenciesFinder
 */
class AggregationDependenciesFinder implements DependenciesFinderInterface
{
    /** @var DependenciesFinderInterface[] */
    private $strategies;

    /**
     * AggregationDependenciesFinder constructor.
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
