<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\ComponentPage;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
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
     * @param array<Component> $processedComponents
     * @param bool $linkedComponentIsDependent
     * @return array<string, mixed>
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
                $inAllowedState = false;
                $dependencies = [];
                foreach ($unitOfCode->inputDependencies() as $dependent) {
                    if ($dependent->component() !== $linkedComponent) {
                        continue;
                    }

                    $dependencies[] = $this->extractDependency($unitOfCode, $dependent, $isAllowed, $inAllowedState);
                }

                $extractedRevertedUnitOfCode = [
                    'name' => $unitOfCode->name(),
                    'dependencies' => $dependencies,
                    'is_allowed' => $isAllowed,
                    'in_allowed_state' => $inAllowedState,
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
            $inAllowedState = false;
            $dependencies = [];
            foreach ($unitOfCode->outputDependencies() as $dependency) {
                $dependencies[] = $this
                    ->extractDependency($dependency, $unitOfCode, $isAllowed, $inAllowedState, true);
            }

            $extractedUnitOfCode = [
                'name' => $unitOfCode->name(),
                'dependencies' => $dependencies,
                'is_allowed' => $isAllowed,
                'in_allowed_state' => $inAllowedState,
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

    /**
     * @param UnitOfCode $dependency
     * @param UnitOfCode $dependent
     * @param bool $isAllowed
     * @param bool $inAllowedState
     * @param bool $isOutputDependency
     * @return array<string, mixed>
     */
    private function extractDependency(
        UnitOfCode $dependency,
        UnitOfCode $dependent,
        bool &$isAllowed,
        bool &$inAllowedState,
        bool $isOutputDependency = false
    ): array {
        $dependencyIsAllowed = $dependency->isAccessibleFromOutside()
            && $dependent->component()->isDependencyAllowed($dependency->component());
        if (!$dependencyIsAllowed) {
            $isAllowed = false;
        }

        $dependencyInAllowedState = $dependent->isDependencyInAllowedState($dependency);
        if ($dependencyInAllowedState) {
            $inAllowedState = true;
        }

        return [
            'name' => $isOutputDependency ? $dependency->name() : $dependent->name(),
            'is_allowed' => $dependencyIsAllowed,
            'in_allowed_state' => $dependencyInAllowedState,
        ];
    }
}
