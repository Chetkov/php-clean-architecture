<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\IndexPage;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\UidGenerator;

/**
 * Class ComponentExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport
 */
class ComponentExtractor
{
    use UidGenerator;

    /**
     * @param Component $component
     * @return array
     */
    public function extract(Component $component): array
    {
        $distanceRate = $component->calculateDistanceRate();
        $distanceRateOverage = $component->calculateDistanceRateOverage();
        $distanceRateNorma = $distanceRate - $distanceRateOverage;
        return [
            'uid' => $this->generateUid($component->name()),
            'name' => $component->name(),
            'abstractness_rate' => $component->calculateAbstractnessRate(),
            'instability_rate' => $component->calculateInstabilityRate(),
            'distance_rate' => $distanceRate,
            'distance_norma' => $distanceRateNorma,
            'distance_overage' => $distanceRateOverage,
            'num_of_dependency' => count($component->getDependencyComponents()),
        ];
    }
}
