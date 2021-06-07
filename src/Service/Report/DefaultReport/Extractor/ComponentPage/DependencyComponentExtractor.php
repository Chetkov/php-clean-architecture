<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\ComponentPage;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\UidGenerator;

/**
 * Class DependencyComponentExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor
 */
class DependencyComponentExtractor
{
    use UidGenerator;

    /**
     * @param Component $component
     * @param Component $linkedComponent
     * @param Component[] $processedComponents
     * @param bool $linkedComponentIsDependent
     * @return array
     */
    public function extract(Component $component, Component $linkedComponent, array $processedComponents, bool $linkedComponentIsDependent = false): array
    {
        $extracted = [
            'name' => $this->generateUid($component->name()),
            'linked_component_name' => $this->generateUid($linkedComponent->name()),
            'units_of_code' => [],
            'reverted_units_of_code' => [],
        ];

        if ($linkedComponentIsDependent) {
            foreach ($linkedComponent->getDependencyUnitsOfCode($component) as $unitOfCode) {
                $isAllowed = true;
                $dependencies = [];
                foreach ($unitOfCode->inputDependencies() as $dependency) {
                    if ($linkedComponentIsDependent && $dependency->component() !== $linkedComponent
                        || !$linkedComponentIsDependent && $dependency->component() !== $component
                    ) {
                        continue;
                    }

                    $dependencyIsAllowed = $unitOfCode->isAccessibleFromOutside()
                        && $dependency->component()->isDependencyAllowed($unitOfCode->component());
                    if (!$dependencyIsAllowed) {
                        $isAllowed = false;
                    }

                    $dependencies[] = [
                        'name' => $dependency->name(),
                        'is_allowed' => $dependencyIsAllowed,
                    ];
                }

                $extractedRevertedUnitOfCode = [
                    'name' => $unitOfCode->name(),
                    'dependencies' => $dependencies,
                    'is_allowed' => $isAllowed,
                ];

                foreach ($processedComponents as $processedComponent) {
                    if ($unitOfCode->belongToComponent($processedComponent)) {
                        $extractedRevertedUnitOfCode['uid'] = $this->generateUid($unitOfCode->name());
                        break;
                    }
                }

                $extracted['reverted_units_of_code'][] = $extractedRevertedUnitOfCode;
            }

            $unitsOfCodes = $linkedComponent->getDependentUnitsOfCode($component);
        } else {
            $unitsOfCodes = $component->getDependentUnitsOfCode($linkedComponent);
        }

        foreach ($unitsOfCodes as $unitOfCode) {
            $isAllowed = true;
            $dependencies = [];
            foreach ($unitOfCode->outputDependencies() as $dependency) {
                $outputDependencyIsAllowed = $dependency->isAccessibleFromOutside()
                    && $unitOfCode->component()->isDependencyAllowed($dependency->component());
                if (!$outputDependencyIsAllowed) {
                    $isAllowed = false;
                }

                $dependencies[] = [
                    'name' => $dependency->name(),
                    'is_allowed' => $outputDependencyIsAllowed,
                ];
            }

            $extractedUnitOfCode = [
                'name' => $unitOfCode->name(),
                'dependencies' => $dependencies,
                'is_allowed' => $isAllowed,
            ];

            foreach ($processedComponents as $processedComponent) {
                if ($unitOfCode->belongToComponent($processedComponent)) {
                    $extractedUnitOfCode['uid'] = $this->generateUid($unitOfCode->name());
                    break;
                }
            }

            $extracted['units_of_code'][] = $extractedUnitOfCode;
        }

        return $extracted;
    }
}
