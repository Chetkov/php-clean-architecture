<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model;

/**
 * Class Restrictions
 * @package Chetkov\PHPCleanArchitecture\Model
 */
class Restrictions
{
    /** @var array<UnitOfCode> */
    private $publicUnitsOfCode = [];

    /** @var array<UnitOfCode> */
    private $privateUnitsOfCode = [];

    /** @var array<Component> */
    private $allowedDependencyComponents = [];

    /** @var array<Component> */
    private $forbiddenDependencyComponents = [];

    /** @var float|null */
    private $maxAllowableDistance;

    /** @var bool */
    private $isAllowedStateEnabled = false;

    /** @var array<string, array<string, array<string, array<string, bool>>>> */
    private $allowedState;

    /**
     * @param array<UnitOfCode> $publicUnitsOfCode
     * @param array<UnitOfCode> $privateUnitsOfCode
     * @param array<Component> $allowedDependencyComponents
     * @param array<Component> $forbiddenDependencyComponents
     * @param array<string, array<string, array<string, array<string, bool>>>> $allowedState
     * @param float|null $maxAllowableDistance
     */
    public function __construct(
        array $publicUnitsOfCode = [],
        array $privateUnitsOfCode = [],
        array $allowedDependencyComponents = [],
        array $forbiddenDependencyComponents = [],
        array $allowedState = [],
        ?float $maxAllowableDistance = null
    ) {
        $this->setPublicUnitsOfCode(...$publicUnitsOfCode);
        $this->setPrivateUnitsOfCode(...$privateUnitsOfCode);
        $this->setAllowedDependencyComponents(...$allowedDependencyComponents);
        $this->setForbiddenDependencyComponents(...$forbiddenDependencyComponents);
        $this->setAllowedState($allowedState);
        $this->setMaxAllowableDistance($maxAllowableDistance);
    }

    /**
     * @param UnitOfCode ...$unitsOfCodes
     * @return $this
     */
    public function setPublicUnitsOfCode(UnitOfCode ...$unitsOfCodes): self
    {
        foreach ($unitsOfCodes as $unitOfCode) {
            $this->addPublicUnitOfCode($unitOfCode);
        }
        return $this;
    }

    /**
     * @param UnitOfCode $unitOfCode
     * @return $this
     */
    public function addPublicUnitOfCode(UnitOfCode $unitOfCode): self
    {
        if (!empty($this->privateUnitsOfCode)) {
            throw new \LogicException('Component cannot contains public and private elements at the same time!');
        }
        if (!in_array($unitOfCode, $this->publicUnitsOfCode, true)) {
            $this->publicUnitsOfCode[] = $unitOfCode;
        }
        return $this;
    }

    /**
     * @param UnitOfCode ...$unitsOfCodes
     * @return $this
     */
    public function setPrivateUnitsOfCode(UnitOfCode ...$unitsOfCodes): self
    {
        foreach ($unitsOfCodes as $unitOfCode) {
            $this->addPrivateUnitOfCode($unitOfCode);
        }
        return $this;
    }

    /**
     * @param UnitOfCode $unitOfCode
     * @return $this
     */
    public function addPrivateUnitOfCode(UnitOfCode $unitOfCode): self
    {
        if (!empty($this->publicUnitsOfCode)) {
            throw new \LogicException('Component cannot contains public and private elements at the same time!');
        }
        if (!in_array($unitOfCode, $this->privateUnitsOfCode, true)) {
            $this->privateUnitsOfCode[] = $unitOfCode;
        }
        return $this;
    }

    /**
     * @param Component ...$components
     * @return $this
     */
    public function setAllowedDependencyComponents(Component ...$components): self
    {
        foreach ($components as $component) {
            $this->addAllowedDependencyComponent($component);
        }
        return $this;
    }

    /**
     * @param Component $component
     * @return $this
     */
    public function addAllowedDependencyComponent(Component $component): self
    {
        if (!empty($this->forbiddenDependencyComponents)) {
            throw new \LogicException('Component cannot have allowed and forbidden dependencies at the same time!');
        }
        if (!in_array($component, $this->allowedDependencyComponents, true)) {
            $this->allowedDependencyComponents[] = $component;
        }
        return $this;
    }

    /**
     * @param Component ...$components
     * @return $this
     */
    public function setForbiddenDependencyComponents(Component ...$components): self
    {
        foreach ($components as $component) {
            $this->addForbiddenDependencyComponent($component);
        }
        return $this;
    }

    /**
     * @param Component $component
     * @return $this
     */
    public function addForbiddenDependencyComponent(Component $component): self
    {
        if (!empty($this->allowedDependencyComponents)) {
            throw new \LogicException('Component cannot have allowed and forbidden dependencies at the same time!');
        }
        if (!in_array($component, $this->forbiddenDependencyComponents, true)) {
            $this->forbiddenDependencyComponents[] = $component;
        }
        return $this;
    }

    /**
     * @param array<string, array<string, array<string, array<string, bool>>>> $allowedState
     * @return Restrictions
     */
    public function setAllowedState(array $allowedState): Restrictions
    {
        $this->isAllowedStateEnabled = true;
        $this->allowedState = $allowedState;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowedStateEnabled(): bool
    {
        return $this->isAllowedStateEnabled;
    }

    /**
     * @param float|null $maxAllowableDistance
     */
    public function setMaxAllowableDistance(?float $maxAllowableDistance): void
    {
        $this->maxAllowableDistance = $maxAllowableDistance;
    }

    /**
     * @param Component $thisComponent
     * @return float
     */
    public function calculateDistanceRateOverage(Component $thisComponent): float
    {
        if ($this->maxAllowableDistance === null) {
            return 0;
        }

        $distanceRate = $thisComponent->calculateDistanceRate();
        return $distanceRate > $this->maxAllowableDistance
            ? $distanceRate - $this->maxAllowableDistance
            : 0;
    }

    /**
     * @param Component $dependency
     * @param Component $thisComponent
     * @return bool
     */
    public function isDependencyAllowed(Component $dependency, Component $thisComponent): bool
    {
        if ($dependency === $thisComponent || $dependency->isPrimitives() || $dependency->isGlobal()) {
            return true;
        }
        if (!empty($this->allowedDependencyComponents)) {
            return in_array($dependency, $this->allowedDependencyComponents, true);
        }
        if (!empty($this->forbiddenDependencyComponents)) {
            return !in_array($dependency, $this->forbiddenDependencyComponents, true);
        }
        return true;
    }

    /**
     * @param Component $dependencyComponent
     * @param Component $thisComponent
     * @return bool
     */
    public function isComponentDependencyInAllowedState(Component $dependencyComponent, Component $thisComponent): bool
    {
        if (!$this->allowedState) {
            return false;
        }

        foreach ($thisComponent->getDependentUnitsOfCode($dependencyComponent) as $dependentUnitOfCode) {
            foreach ($dependentUnitOfCode->outputDependencies($dependencyComponent) as $dependencyUnitOfCode) {
                if (!isset($this->allowedState[$dependencyComponent->name()][$dependentUnitOfCode->name()][$dependencyUnitOfCode->name()])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param UnitOfCode $dependencyUnitOfCode
     * @param UnitOfCode $thisUnitOfCode
     * @return bool
     */
    public function isUnitOfCodeDependencyInAllowedState(UnitOfCode $dependencyUnitOfCode, UnitOfCode $thisUnitOfCode): bool
    {
        if (!$this->allowedState) {
            return false;
        }

        return isset($this->allowedState[$dependencyUnitOfCode->component()->name()][$thisUnitOfCode->name()][$dependencyUnitOfCode->name()]);
    }

    /**
     * @param UnitOfCode $unitOfCode
     * @return bool
     */
    public function isUnitOfCodeAccessibleFromOutside(UnitOfCode $unitOfCode): bool
    {
        if ($unitOfCode->isPrimitive() || $unitOfCode->belongToGlobalNamespace()) {
            return true;
        }
        if (!empty($this->publicUnitsOfCode)) {
            return in_array($unitOfCode, $this->publicUnitsOfCode, true);
        }
        if (!empty($this->privateUnitsOfCode)) {
            return !in_array($unitOfCode, $this->privateUnitsOfCode, true);
        }
        return true;
    }

    /**
     * @param Component $thisComponent
     * @return array<Component>
     */
    public function getIllegalDependencyComponents(Component $thisComponent): array
    {
        $uniqueIllegalDependencyComponents = [];
        foreach ($thisComponent->getDependencyComponents() as $dependencyComponent) {
            if ($this->isDependencyAllowed($dependencyComponent, $thisComponent)) {
                continue;
            }

            if ($thisComponent->isDependencyInAllowedState($dependencyComponent)) {
                continue;
            }

            $uniqueIllegalDependencyComponents[spl_object_hash($dependencyComponent)] = $dependencyComponent;
        }
        return array_values($uniqueIllegalDependencyComponents);
    }

    /**
     * @param Component $thisComponent
     * @param bool $onlyFromAllowedComponents
     * @return array<UnitOfCode>
     */
    public function getIllegalDependencyUnitsOfCode(Component $thisComponent, bool $onlyFromAllowedComponents = false): array
    {
        $uniqueIllegalDependencies = [];
        foreach ($thisComponent->unitsOfCode() as $unitOfCode) {
            foreach ($unitOfCode->outputDependencies() as $dependency) {
                if ($dependency->belongToComponent($thisComponent)) {
                    continue;
                }

                if ($unitOfCode->isDependencyInAllowedState($dependency)) {
                    continue;
                }

                $isDependencyAllowed = $this->isDependencyAllowed($dependency->component(), $thisComponent);
                $isDependencyAccessibleFromOutside = $dependency->isAccessibleFromOutside();
                if ($onlyFromAllowedComponents) {
                    if (!$isDependencyAllowed) {
                        continue;
                    }
                    if (!$isDependencyAccessibleFromOutside) {
                        $uniqueIllegalDependencies[spl_object_hash($dependency)] = $dependency;
                    }
                } elseif (!$isDependencyAllowed) {
                    $uniqueIllegalDependencies[spl_object_hash($dependency)] = $dependency;
                } elseif (!$isDependencyAccessibleFromOutside) {
                    $uniqueIllegalDependencies[spl_object_hash($dependency)] = $dependency;
                }
            }
        }
        return array_values($uniqueIllegalDependencies);
    }
}
