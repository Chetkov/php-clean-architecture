<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\SpaReport;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

final class ReportDataBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Component ...$components): array
    {
        $enabledComponents = $this->enabledComponents(...$components);
        $units = [];
        $dependencies = [];
        $violations = [];

        foreach ($enabledComponents as $component) {
            foreach ($component->unitsOfCode() as $unitOfCode) {
                $units[$this->unitId($unitOfCode)] = $this->unitData($unitOfCode);
                foreach ($unitOfCode->outputDependencies() as $dependencyUnitOfCode) {
                    if ($this->shouldSkipDependency($dependencyUnitOfCode)) {
                        continue;
                    }

                    $dependency = $this->dependencyData($unitOfCode, $dependencyUnitOfCode);
                    $dependencies[$dependency['id']] = $dependency;
                    foreach ($this->violationsForDependency($unitOfCode, $dependencyUnitOfCode) as $violation) {
                        $violations[$violation['id']] = $violation;
                    }
                }
            }
        }

        $componentData = array_map(function (Component $component): array {
            return $this->componentData($component);
        }, $enabledComponents);

        return [
            'schemaVersion' => 1,
            'generatedAt' => date(DATE_ATOM),
            'summary' => [
                'components' => count($componentData),
                'units' => count($units),
                'dependencies' => count($dependencies),
                'violations' => count($violations),
                'activeViolations' => count(array_filter($violations, static function (array $violation): bool {
                    return $violation['status'] === 'active';
                })),
                'legacy' => $this->legacyMetrics($units),
            ],
            'components' => array_values($componentData),
            'units' => array_values($units),
            'dependencies' => array_values($dependencies),
            'violations' => array_values($violations),
        ];
    }

    /**
     * @return array<Component>
     */
    private function enabledComponents(Component ...$components): array
    {
        return array_values(array_filter($components, static function (Component $component): bool {
            return $component->isEnabledForAnalysis();
        }));
    }

    /**
     * @return array<string, mixed>
     */
    private function componentData(Component $component): array
    {
        $dependencyComponents = $component->getDependencyComponents();
        $dependentComponents = $component->getDependentComponents();
        $distanceOverage = $component->calculateDistanceRateOverage();

        return [
            'id' => $this->componentId($component),
            'name' => $component->name(),
            'metrics' => [
                'abstractness' => $component->calculateAbstractnessRate(),
                'instability' => $component->calculateInstabilityRate(),
                'distance' => $component->calculateDistanceRate(),
                'distanceOverage' => $distanceOverage,
                'primitiveness' => $component->calculatePrimitivenessRate(),
                'units' => count($component->unitsOfCode()),
                'incomingComponents' => count($dependentComponents),
                'outgoingComponents' => count($dependencyComponents),
            ],
            'incomingComponentIds' => array_map(function (Component $dependentComponent): string {
                return $this->componentId($dependentComponent);
            }, $dependentComponents),
            'outgoingComponentIds' => array_map(function (Component $dependencyComponent): string {
                return $this->componentId($dependencyComponent);
            }, $dependencyComponents),
            'health' => [
                'hasDistanceOverage' => $distanceOverage > 0,
                'hasForbiddenDependencies' => count($component->getIllegalDependencyComponents()) > 0,
                'hasPrivateApiDependencies' => count($component->getIllegalDependencyUnitsOfCode(true)) > 0,
            ],
            'legacy' => $this->legacyMetrics($component->unitsOfCode()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unitData(UnitOfCode $unitOfCode): array
    {
        return [
            'id' => $this->unitId($unitOfCode),
            'name' => $unitOfCode->name(),
            'shortName' => $this->shortName($unitOfCode->name()),
            'path' => $unitOfCode->path(),
            'componentId' => $this->componentId($unitOfCode->component()),
            'componentName' => $unitOfCode->component()->name(),
            'type' => $this->unitType($unitOfCode),
            'isPublic' => $unitOfCode->isAccessibleFromOutside(),
            'isAbstract' => $unitOfCode->isAbstract(),
            'isLegacy' => $unitOfCode->isLegacy(),
            'linesOfCode' => $unitOfCode->linesOfCode(),
            'metrics' => [
                'instability' => $unitOfCode->calculateInstabilityRate(),
                'primitiveness' => $unitOfCode->calculatePrimitivenessRate(),
                'incoming' => count($unitOfCode->inputDependencies()),
                'outgoing' => count($unitOfCode->outputDependencies()),
            ],
        ];
    }

    /**
     * @return array{id: string, fromUnitId: string, toUnitId: string, fromComponentId: string, toComponentId: string, fromUnitName: string, toUnitName: string, fromComponentName: string, toComponentName: string, isInternal: bool, isComponentAllowed: bool, isTargetPublic: bool, isAllowedState: bool}
     */
    private function dependencyData(UnitOfCode $unitOfCode, UnitOfCode $dependencyUnitOfCode): array
    {
        $component = $unitOfCode->component();
        $dependencyComponent = $dependencyUnitOfCode->component();
        $isComponentAllowed = $component->isDependencyAllowed($dependencyComponent);
        $isTargetPublic = $dependencyUnitOfCode->isAccessibleFromOutside();

        return [
            'id' => $this->dependencyId($unitOfCode, $dependencyUnitOfCode),
            'fromUnitId' => $this->unitId($unitOfCode),
            'toUnitId' => $this->unitId($dependencyUnitOfCode),
            'fromComponentId' => $this->componentId($component),
            'toComponentId' => $this->componentId($dependencyComponent),
            'fromUnitName' => $unitOfCode->name(),
            'toUnitName' => $dependencyUnitOfCode->name(),
            'fromComponentName' => $component->name(),
            'toComponentName' => $dependencyComponent->name(),
            'isInternal' => $dependencyUnitOfCode->belongToComponent($component),
            'isComponentAllowed' => $isComponentAllowed,
            'isTargetPublic' => $isTargetPublic,
            'isAllowedState' => $this->isAllowedStateViolation($unitOfCode, $dependencyUnitOfCode, $isComponentAllowed, $isTargetPublic),
        ];
    }

    /**
     * @return array<array{id: string, dependencyId: string, type: string, status: string, fromUnitId: string, toUnitId: string, fromComponentId: string, toComponentId: string, message: string}>
     */
    private function violationsForDependency(UnitOfCode $unitOfCode, UnitOfCode $dependencyUnitOfCode): array
    {
        $component = $unitOfCode->component();
        $dependencyComponent = $dependencyUnitOfCode->component();
        $isAllowedState = $unitOfCode->isDependencyInAllowedState($dependencyUnitOfCode);
        $violations = [];

        if (!$component->isDependencyAllowed($dependencyComponent)) {
            $violations[] = $this->violationData(
                'forbidden-component',
                $isAllowedState,
                $unitOfCode,
                $dependencyUnitOfCode,
                sprintf('"%s" can not depend on "%s".', $component->name(), $dependencyComponent->name())
            );
        }

        if (
            $component->isDependencyAllowed($dependencyComponent)
            && !$dependencyUnitOfCode->belongToComponent($component)
            && !$dependencyUnitOfCode->isAccessibleFromOutside()
        ) {
            $violations[] = $this->violationData(
                'private-unit',
                $isAllowedState,
                $unitOfCode,
                $dependencyUnitOfCode,
                sprintf('"%s" uses non public "%s".', $component->name(), $dependencyUnitOfCode->name())
            );
        }

        return $violations;
    }

    private function isAllowedStateViolation(
        UnitOfCode $unitOfCode,
        UnitOfCode $dependencyUnitOfCode,
        bool $isComponentAllowed,
        bool $isTargetPublic
    ): bool {
        if (!$unitOfCode->isDependencyInAllowedState($dependencyUnitOfCode)) {
            return false;
        }

        return !$isComponentAllowed || !$isTargetPublic;
    }

    /**
     * @return array{id: string, dependencyId: string, type: string, status: string, fromUnitId: string, toUnitId: string, fromComponentId: string, toComponentId: string, message: string}
     */
    private function violationData(
        string $type,
        bool $isAllowedState,
        UnitOfCode $unitOfCode,
        UnitOfCode $dependencyUnitOfCode,
        string $message
    ): array {
        return [
            'id' => $type . ':' . $this->dependencyId($unitOfCode, $dependencyUnitOfCode),
            'dependencyId' => $this->dependencyId($unitOfCode, $dependencyUnitOfCode),
            'type' => $type,
            'status' => $isAllowedState ? 'allowed-state' : 'active',
            'fromUnitId' => $this->unitId($unitOfCode),
            'toUnitId' => $this->unitId($dependencyUnitOfCode),
            'fromComponentId' => $this->componentId($unitOfCode->component()),
            'toComponentId' => $this->componentId($dependencyUnitOfCode->component()),
            'message' => $message,
        ];
    }

    private function shouldSkipDependency(UnitOfCode $dependencyUnitOfCode): bool
    {
        return $dependencyUnitOfCode->belongToGlobalNamespace()
            || $dependencyUnitOfCode->isPrimitive();
    }

    private function componentId(Component $component): string
    {
        return 'component:' . $this->generateUid($component->name());
    }

    private function unitId(UnitOfCode $unitOfCode): string
    {
        return 'unit:' . $this->generateUid($unitOfCode->name());
    }

    private function dependencyId(UnitOfCode $unitOfCode, UnitOfCode $dependencyUnitOfCode): string
    {
        return $this->unitId($unitOfCode) . '->' . $this->unitId($dependencyUnitOfCode);
    }

    private function shortName(string $name): string
    {
        $parts = explode('\\', $name);

        return end($parts) ?: $name;
    }

    private function unitType(UnitOfCode $unitOfCode): string
    {
        if ($unitOfCode->isInterface()) {
            return 'interface';
        }
        if ($unitOfCode->isTrait()) {
            return 'trait';
        }
        if ($unitOfCode->isClass()) {
            return $unitOfCode->isAbstract() ? 'abstract-class' : 'class';
        }
        if ($unitOfCode->isPrimitive()) {
            return 'primitive';
        }

        return 'unknown';
    }

    /**
     * @param array<UnitOfCode|array<string, mixed>> $units
     *
     * @return array{units: int, legacyUnits: int, modernUnits: int, linesOfCode: int, legacyLinesOfCode: int, modernLinesOfCode: int, legacyRate: float, modernRate: float}
     */
    private function legacyMetrics(array $units): array
    {
        $totalUnits = count($units);
        $legacyUnits = count(array_filter($units, static function ($unit): bool {
            if ($unit instanceof UnitOfCode) {
                return $unit->isLegacy();
            }

            return !empty($unit['isLegacy']);
        }));
        $modernUnits = $totalUnits - $legacyUnits;
        $files = [];
        foreach ($units as $index => $unit) {
            $path = null;
            $isLegacy = false;
            $linesOfCode = 0;

            if ($unit instanceof UnitOfCode) {
                $path = $unit->path();
                $isLegacy = $unit->isLegacy();
                $linesOfCode = $unit->linesOfCode();
            } else {
                $path = $unit['path'] ?? null;
                $isLegacy = !empty($unit['isLegacy']);
                $linesOfCode = (int) ($unit['linesOfCode'] ?? 0);
            }

            $key = $path ? 'path:' . $path : 'unit:' . (string) $index;
            if (!isset($files[$key])) {
                $files[$key] = [
                    'isLegacy' => $isLegacy,
                    'linesOfCode' => max(0, $linesOfCode),
                ];
                continue;
            }

            $files[$key]['isLegacy'] = $files[$key]['isLegacy'] || $isLegacy;
            $files[$key]['linesOfCode'] = max($files[$key]['linesOfCode'], max(0, $linesOfCode));
        }

        $totalLinesOfCode = array_sum(array_column($files, 'linesOfCode'));
        $legacyLinesOfCode = array_sum(array_map(static function (array $file): int {
            return $file['isLegacy'] ? $file['linesOfCode'] : 0;
        }, $files));
        $modernLinesOfCode = $totalLinesOfCode - $legacyLinesOfCode;

        return [
            'units' => $totalUnits,
            'legacyUnits' => $legacyUnits,
            'modernUnits' => $modernUnits,
            'linesOfCode' => $totalLinesOfCode,
            'legacyLinesOfCode' => $legacyLinesOfCode,
            'modernLinesOfCode' => $modernLinesOfCode,
            'legacyRate' => $totalLinesOfCode > 0 ? $legacyLinesOfCode / $totalLinesOfCode : 0.0,
            'modernRate' => $totalLinesOfCode > 0 ? $modernLinesOfCode / $totalLinesOfCode : 0.0,
        ];
    }

    private function generateUid(string $name): string
    {
        return strtolower((string) preg_replace('/[ \/\\\]/', '-', $name));
    }
}
