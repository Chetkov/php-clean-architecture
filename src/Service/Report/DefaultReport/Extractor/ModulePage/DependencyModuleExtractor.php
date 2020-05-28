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
     * @param Module $currentModule
     * @param Module $linkedModule
     * @param Module[] $processedModules
     * @param bool $linkedModuleIsDependent
     * @return array
     */
    public function extract(Module $currentModule, Module $linkedModule, array $processedModules, bool $linkedModuleIsDependent = false): array
    {
        $extracted = [
            'name' => $this->generateUid($currentModule->name()),
            'units_of_code' => [],
        ];

        $unitsOfCodes = $linkedModuleIsDependent
            ? $linkedModule->getDependentUnitsOfCode($currentModule)
            : $currentModule->getDependentUnitsOfCode($linkedModule);

        foreach ($unitsOfCodes as $unitOfCode) {
            $isAllowed = true;
            $outputDependencies = [];
            foreach ($unitOfCode->outputDependencies() as $outputDependency) {
                if (!$outputDependencyIsAllowed = $outputDependency->isAccessibleFromOutside()) {
                    $isAllowed = false;
                }

                $outputDependencies[] = [
                    'name' => $outputDependency->name(),
                    'is_allowed' => $outputDependencyIsAllowed,
                ];
            }

            $extractedUnitOfCode = [
                'name' => $unitOfCode->name(),
                'output_dependencies' => $outputDependencies,
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
