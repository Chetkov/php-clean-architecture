<?php

namespace Chetkov\PHPCleanArchitecture\Service\Report\Extractor\UnitOfCodePage;

use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Report\UidGenerator;

/**
 * Class DependencyUnitOfCodeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\Extractor\UnitOfCodePage
 */
class DependencyUnitOfCodeExtractor
{
    use UidGenerator;

    /**
     * @param UnitOfCode $unitOfCode
     * @param UnitOfCode $dependency
     * @param Module[] $processedModules
     * @param bool $isInputDependency
     * @return array
     */
    public function extract(UnitOfCode $unitOfCode, UnitOfCode $dependency, array $processedModules, bool $isInputDependency = true): array
    {
        $data = [
            'name' => $dependency->name(),
        ];

        foreach ($processedModules as $processedModule) {
            if ($dependency->belongToModule($processedModule)) {
                $data['uid'] = $this->generateUid($dependency->name());
                break;
            }
        }

        $data['is_allowed'] = $isInputDependency
            ? $unitOfCode->belongToModule($dependency->module()) || ($dependency->module()->isDependencyAllowed($unitOfCode->module()) && $unitOfCode->isAccessibleFromOutside())
            : $dependency->belongToModule($unitOfCode->module()) || ($unitOfCode->module()->isDependencyAllowed($dependency->module()) && $dependency->isAccessibleFromOutside());

        return $data;
    }
}
