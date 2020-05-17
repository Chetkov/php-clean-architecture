<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\Extractor\IndexPage;

use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Service\Report\UidGenerator;

/**
 * Class ModuleExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report
 */
class ModuleExtractor
{
    use UidGenerator;

    /**
     * @param Module $module
     * @return array
     */
    public function extract(Module $module): array
    {
        $distanceRate = $module->calculateDistanceRate();
        $distanceRateOverage = $module->calculateDistanceRateOverage();
        $distanceRateNorma = $distanceRate - $distanceRateOverage;
        return [
            'uid' => $this->generateUid($module->name()),
            'name' => $module->name(),
            'abstractness_rate' => $module->calculateAbstractnessRate(),
            'instability_rate' => $module->calculateInstabilityRate(),
            'distance_rate' => $distanceRate,
            'distance_norma' => $distanceRateNorma,
            'distance_overage' => $distanceRateOverage,
            'num_of_dependency' => count($module->getDependencyModules()),
        ];
    }
}
