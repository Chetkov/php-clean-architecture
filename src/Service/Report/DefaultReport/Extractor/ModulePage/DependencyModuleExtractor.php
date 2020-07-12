<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\ModulePage;

use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\UidGenerator;

/**
 * Class DependencyModuleExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor
 */
class DependencyModuleExtractor
{
    use UidGenerator;

    /**
     * @param Module $module
     * @param Module $linkedModule
     * @param Module[] $processedModules
     * @param bool $linkedModuleIsDependent
     * @return array
     */
    public function extract(Module $module, Module $linkedModule, array $processedModules, bool $linkedModuleIsDependent = false): array
    {
        $extracted = [
            'name' => $this->generateUid($module->name()),
            'linked_module_name' => $this->generateUid($linkedModule->name()),
            'units_of_code' => [],
            'reverted_units_of_code' => [],
        ];

        if ($linkedModuleIsDependent) {
            foreach ($linkedModule->getDependencyUnitsOfCode($module) as $unitOfCode) {
                $isAllowed = true;
                $dependencies = [];
                foreach ($unitOfCode->inputDependencies() as $dependency) {
                    if ($linkedModuleIsDependent && $dependency->module() !== $linkedModule
                        || !$linkedModuleIsDependent && $dependency->module() !== $module
                    ) {
                        continue;
                    }

                    $dependencyIsAllowed = $unitOfCode->isAccessibleFromOutside()
                        && $dependency->module()->isDependencyAllowed($unitOfCode->module());
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

                foreach ($processedModules as $processedModule) {
                    if ($unitOfCode->belongToModule($processedModule)) {
                        $extractedRevertedUnitOfCode['uid'] = $this->generateUid($unitOfCode->name());
                        break;
                    }
                }

                $extracted['reverted_units_of_code'][] = $extractedRevertedUnitOfCode;
            }

            $unitsOfCodes = $linkedModule->getDependentUnitsOfCode($module);
        } else {
            $unitsOfCodes = $module->getDependentUnitsOfCode($linkedModule);
        }

        foreach ($unitsOfCodes as $unitOfCode) {
            $isAllowed = true;
            $dependencies = [];
            foreach ($unitOfCode->outputDependencies() as $dependency) {
                $outputDependencyIsAllowed = $dependency->isAccessibleFromOutside()
                    && $unitOfCode->module()->isDependencyAllowed($dependency->module());
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

            foreach ($processedModules as $processedModule) {
                if ($unitOfCode->belongToModule($processedModule)) {
                    $extractedUnitOfCode['uid'] = $this->generateUid($unitOfCode->name());
                    break;
                }
            }

            $extracted['units_of_code'][] = $extractedUnitOfCode;
        }

        return $extracted;
    }
}
