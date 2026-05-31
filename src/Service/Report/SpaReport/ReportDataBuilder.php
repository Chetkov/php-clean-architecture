<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\SpaReport;

use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;

final class ReportDataBuilder
{
    private const DEPENDENCY_FLAG_INTERNAL = 1;
    private const DEPENDENCY_FLAG_COMPONENT_ALLOWED = 2;
    private const DEPENDENCY_FLAG_TARGET_PUBLIC = 4;
    private const DEPENDENCY_FLAG_ALLOWED_STATE = 8;

    /**
     * @return array<string, mixed>
     */
    public function build(Component ...$components): array
    {
        $enabledComponents = $this->enabledComponents(...$components);
        $enabledComponentIds = [];
        foreach ($enabledComponents as $component) {
            $enabledComponentIds[$this->componentId($component)] = 1;
        }
        $units = [];
        $externalUnits = [];
        $externalComponents = [];
        $dependencies = [];
        $componentEdges = [];
        $violations = [];

        foreach ($enabledComponents as $component) {
            foreach ($component->unitsOfCode() as $unitOfCode) {
                $units[$this->unitId($unitOfCode)] = $this->unitData($unitOfCode);
            }
        }

        foreach ($enabledComponents as $component) {
            foreach ($component->unitsOfCode() as $unitOfCode) {
                foreach ($unitOfCode->outputDependencies() as $dependencyUnitOfCode) {
                    if ($this->shouldSkipDependency($dependencyUnitOfCode)) {
                        continue;
                    }

                    $dependencyFacts = $this->dependencyFacts($unitOfCode, $dependencyUnitOfCode);
                    $dependencies[$this->dependencyId($unitOfCode, $dependencyUnitOfCode)] = $this->dependencyData($dependencyFacts);
                    $this->rememberComponentEdge($componentEdges, $dependencyFacts);
                    $this->rememberExternalReference($dependencyUnitOfCode, $units, $externalUnits, $enabledComponentIds, $externalComponents);
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
            'schemaVersion' => 4,
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
            'externalComponents' => array_values($externalComponents),
            'externalUnits' => array_values($externalUnits),
            'componentEdges' => $this->componentEdgeData($componentEdges),
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
     * @param array<string, array<string, mixed>> $units
     * @param array<string, array<string, mixed>> $externalUnits
     * @param array<string, int> $enabledComponentIds
     * @param array<string, array<string, mixed>> $externalComponents
     * @return void
     */
    private function rememberExternalReference(
        UnitOfCode $unitOfCode,
        array $units,
        array &$externalUnits,
        array $enabledComponentIds,
        array &$externalComponents
    ): void {
        $unitId = $this->unitId($unitOfCode);
        if (!isset($units[$unitId]) && !isset($externalUnits[$unitId])) {
            $externalUnits[$unitId] = $this->unitReferenceData($unitOfCode);
        }

        $component = $unitOfCode->component();
        $componentId = $this->componentId($component);
        if (!isset($enabledComponentIds[$componentId]) && !isset($externalComponents[$componentId])) {
            $externalComponents[$componentId] = [
                'id' => $componentId,
                'name' => $component->name(),
            ];
        }
    }

    /**
     * @return array{id: string, name: string, shortName: string, path: string|null, componentId: string}
     */
    private function unitReferenceData(UnitOfCode $unitOfCode): array
    {
        return [
            'id' => $this->unitId($unitOfCode),
            'name' => $unitOfCode->name(),
            'shortName' => $this->shortName($unitOfCode->name()),
            'path' => $unitOfCode->path(),
            'componentId' => $this->componentId($unitOfCode->component()),
        ];
    }

    /**
     * @return array{fromUnitId: string, toUnitId: string, fromComponentId: string, toComponentId: string, isInternal: bool, isComponentAllowed: bool, isTargetPublic: bool, isAllowedState: bool, flags: int, status: string}
     */
    private function dependencyFacts(UnitOfCode $unitOfCode, UnitOfCode $dependencyUnitOfCode): array
    {
        $component = $unitOfCode->component();
        $dependencyComponent = $dependencyUnitOfCode->component();
        $isComponentAllowed = $component->isDependencyAllowed($dependencyComponent);
        $isTargetPublic = $dependencyUnitOfCode->isAccessibleFromOutside();
        $isInternal = $dependencyUnitOfCode->belongToComponent($component);
        $isAllowedState = $this->isAllowedStateViolation(
            $unitOfCode,
            $dependencyUnitOfCode,
            $isComponentAllowed,
            $isTargetPublic
        );
        $flags = 0;
        if ($isInternal) {
            $flags |= self::DEPENDENCY_FLAG_INTERNAL;
        }
        if ($isComponentAllowed) {
            $flags |= self::DEPENDENCY_FLAG_COMPONENT_ALLOWED;
        }
        if ($isTargetPublic) {
            $flags |= self::DEPENDENCY_FLAG_TARGET_PUBLIC;
        }
        if ($isAllowedState) {
            $flags |= self::DEPENDENCY_FLAG_ALLOWED_STATE;
        }

        $isPrivateApiViolation = !$isInternal && $isComponentAllowed && !$isTargetPublic;

        return [
            'fromUnitId' => $this->unitId($unitOfCode),
            'toUnitId' => $this->unitId($dependencyUnitOfCode),
            'fromComponentId' => $this->componentId($component),
            'toComponentId' => $this->componentId($dependencyComponent),
            'isInternal' => $isInternal,
            'isComponentAllowed' => $isComponentAllowed,
            'isTargetPublic' => $isTargetPublic,
            'isAllowedState' => $isAllowedState,
            'flags' => $flags,
            'status' => $this->dependencyCountKey(!$isComponentAllowed, $isAllowedState, $isPrivateApiViolation, $isInternal),
        ];
    }

    /**
     * @param array{fromUnitId: string, toUnitId: string, fromComponentId: string, toComponentId: string, flags: int} $dependencyFacts
     *
     * @return array{0: string, 1: string, 2: string, 3: string, 4: int}
     */
    private function dependencyData(array $dependencyFacts): array
    {
        return [
            $dependencyFacts['fromUnitId'],
            $dependencyFacts['toUnitId'],
            $dependencyFacts['fromComponentId'],
            $dependencyFacts['toComponentId'],
            $dependencyFacts['flags'],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $componentEdges
     * @param array{fromUnitId: string, toUnitId: string, fromComponentId: string, toComponentId: string, isInternal: bool, status: string} $dependencyFacts
     *
     * @return void
     */
    private function rememberComponentEdge(array &$componentEdges, array $dependencyFacts): void
    {
        if ($dependencyFacts['isInternal'] || $dependencyFacts['fromComponentId'] === $dependencyFacts['toComponentId']) {
            return;
        }

        $edgeId = $dependencyFacts['fromComponentId'] . '->' . $dependencyFacts['toComponentId'];
        if (!isset($componentEdges[$edgeId])) {
            $componentEdges[$edgeId] = [
                'id' => $edgeId,
                'fromComponentId' => $dependencyFacts['fromComponentId'],
                'toComponentId' => $dependencyFacts['toComponentId'],
                'weight' => 0,
                'sourceUnitIds' => [],
                'targetUnitIds' => [],
                'counts' => [
                    'allowed' => 0,
                    'allowedState' => 0,
                    'blocked' => 0,
                    'internal' => 0,
                    'private' => 0,
                ],
            ];
        }

        $componentEdges[$edgeId] = $this->incrementComponentEdge($componentEdges[$edgeId], $dependencyFacts);
    }

    /**
     * @param array<string, array<string, mixed>> $componentEdges
     *
     * @return array<array{id: string, fromComponentId: string, toComponentId: string, weight: int, sourceUnitCount: int, targetUnitCount: int, counts: array{allowed: int, allowedState: int, blocked: int, internal: int, private: int}, status: string}>
     */
    private function componentEdgeData(array $componentEdges): array
    {
        $result = [];
        foreach ($componentEdges as $edge) {
            $counts = $this->dependencyStatusCounts($edge['counts'] ?? []);
            $sourceUnitIds = is_array($edge['sourceUnitIds'] ?? null) ? $edge['sourceUnitIds'] : [];
            $targetUnitIds = is_array($edge['targetUnitIds'] ?? null) ? $edge['targetUnitIds'] : [];

            $result[] = [
                'id' => $this->stringValue($edge, 'id') ?? '',
                'fromComponentId' => $this->stringValue($edge, 'fromComponentId') ?? '',
                'toComponentId' => $this->stringValue($edge, 'toComponentId') ?? '',
                'weight' => $this->intValue($edge, 'weight'),
                'sourceUnitCount' => count($sourceUnitIds),
                'targetUnitCount' => count($targetUnitIds),
                'counts' => $counts,
                'status' => $this->worstDependencyStatus($counts),
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $edge
     * @param array{fromComponentId: string, toComponentId: string, fromUnitId: string, toUnitId: string, status: string} $dependencyFacts
     *
     * @return array<string, mixed>
     */
    private function incrementComponentEdge(array $edge, array $dependencyFacts): array
    {
        $counts = $this->dependencyStatusCounts($edge['counts'] ?? []);
        $status = $dependencyFacts['status'];
        if (array_key_exists($status, $counts)) {
            $counts[$status]++;
        }

        $sourceUnitIds = is_array($edge['sourceUnitIds'] ?? null) ? $edge['sourceUnitIds'] : [];
        $targetUnitIds = is_array($edge['targetUnitIds'] ?? null) ? $edge['targetUnitIds'] : [];
        $sourceUnitIds[$dependencyFacts['fromUnitId']] = true;
        $targetUnitIds[$dependencyFacts['toUnitId']] = true;

        $edge['weight'] = $this->intValue($edge, 'weight') + 1;
        $edge['sourceUnitIds'] = $sourceUnitIds;
        $edge['targetUnitIds'] = $targetUnitIds;
        $edge['counts'] = $counts;

        return $edge;
    }

    /**
     * @param mixed $counts
     *
     * @return array{allowed: int, allowedState: int, blocked: int, internal: int, private: int}
     */
    private function dependencyStatusCounts($counts): array
    {
        if (!is_array($counts)) {
            $counts = [];
        }
        $counts = $this->stringKeyedArray($counts);

        return [
            'allowed' => $this->intValue($counts, 'allowed'),
            'allowedState' => $this->intValue($counts, 'allowedState'),
            'blocked' => $this->intValue($counts, 'blocked'),
            'internal' => $this->intValue($counts, 'internal'),
            'private' => $this->intValue($counts, 'private'),
        ];
    }

    private function dependencyCountKey(
        bool $isForbiddenComponent,
        bool $isAllowedState,
        bool $isPrivateApiViolation,
        bool $isInternal
    ): string {
        if ($isAllowedState) {
            return 'allowedState';
        }
        if ($isForbiddenComponent) {
            return 'blocked';
        }
        if ($isPrivateApiViolation) {
            return 'private';
        }
        if ($isInternal) {
            return 'internal';
        }

        return 'allowed';
    }

    /**
     * @param array{allowed: int, allowedState: int, blocked: int, internal: int, private: int} $counts
     */
    private function worstDependencyStatus(array $counts): string
    {
        if ($counts['blocked'] > 0) {
            return 'blocked';
        }
        if ($counts['private'] > 0) {
            return 'private';
        }
        if ($counts['allowedState'] > 0) {
            return 'allowed-state';
        }
        if ($counts['internal'] > 0) {
            return 'internal';
        }

        return 'allowed';
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
            if ($unit instanceof UnitOfCode) {
                $path = $unit->path();
                $isLegacy = $unit->isLegacy();
                $linesOfCode = $unit->linesOfCode();
            } else {
                $pathValue = $unit['path'] ?? null;
                $path = is_string($pathValue) && $pathValue !== '' ? $pathValue : null;
                $isLegacy = !empty($unit['isLegacy']);
                $linesOfCodeValue = $unit['linesOfCode'] ?? 0;
                $linesOfCode = is_int($linesOfCodeValue) ? $linesOfCodeValue : 0;
            }

            $key = $path ? 'path:' . $path : 'unit:' . (string) $index;
            if (!isset($files[$key])) {
                $files[$key] = [
                    'isLegacy' => $isLegacy,
                    'linesOfCode' => max(0, $linesOfCode),
                ];
                continue;
            }

            $existingIsLegacy = $files[$key]['isLegacy'];
            $existingLinesOfCode = $files[$key]['linesOfCode'];
            $files[$key]['isLegacy'] = $existingIsLegacy === true || $isLegacy;
            $files[$key]['linesOfCode'] = max(
                $existingLinesOfCode,
                max(0, $linesOfCode)
            );
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

    /**
     * @param array<string, mixed> $data
     */
    private function stringValue(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function intValue(array $data, string $key): int
    {
        $value = $data[$key] ?? 0;

        return is_int($value) ? $value : 0;
    }

    /**
     * @param array<mixed, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function stringKeyedArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
