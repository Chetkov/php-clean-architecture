<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage;

use Chetkov\PHPCleanArchitecture\Model\ComponentInterface;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\UidGenerator;

/**
 * Class DependencyUnitOfCodeExtractor
 * @package Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Extractor\UnitOfCodePage
 */
class DependencyUnitOfCodeExtractor
{
    use UidGenerator;

    /**
     * @param UnitOfCode $unitOfCode
     * @param UnitOfCode $dependency
     * @param array<ComponentInterface> $processedComponents
     * @param bool $isInputDependency
     * @return array<string, mixed>
     */
    public function extract(UnitOfCode $unitOfCode, UnitOfCode $dependency, array $processedComponents, bool $isInputDependency = true): array
    {
        $data = [
            'name' => $dependency->name(),
        ];

        foreach ($processedComponents as $processedComponent) {
            if ($dependency->belongToComponent($processedComponent)) {
                $data['uid'] = $this->generateUid($dependency->name());
                break;
            }
        }

        $data['is_allowed'] = $isInputDependency
            ? $unitOfCode->belongToComponent($dependency->component()) || ($dependency->component()->isDependencyAllowed($unitOfCode->component()) && $unitOfCode->isAccessibleFromOutside())
            : $dependency->belongToComponent($unitOfCode->component()) || ($unitOfCode->component()->isDependencyAllowed($dependency->component()) && $dependency->isAccessibleFromOutside());

        $data['in_allowed_state'] = $isInputDependency
            ? $dependency->isDependencyInAllowedState($unitOfCode)
            : $unitOfCode->isDependencyInAllowedState($dependency);

        return $data;
    }
}
