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

    /** @var array<ComponentInterface> */
    private $allowedDependencyComponents = [];

    /** @var array<ComponentInterface> */
    private $forbiddenDependencyComponents = [];

    /** @var float|null */
    private $maxAllowableDistance;

    /** @var array<string, array<string, array<string, array<string, bool>>>> */
    private $allowedState;

    /**
     * @param array<UnitOfCode> $publicUnitsOfCode
     * @param array<UnitOfCode> $privateUnitsOfCode
     * @param array<ComponentInterface> $allowedDependencyComponents
     * @param array<ComponentInterface> $forbiddenDependencyComponents
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
     * @param ComponentInterface ...$components
     * @return $this
     */
    public function setAllowedDependencyComponents(ComponentInterface ...$components): self
    {
        foreach ($components as $component) {
            $this->addAllowedDependencyComponent($component);
        }
        return $this;
    }

    /**
     * @param ComponentInterface $component
     * @return $this
     */
    public function addAllowedDependencyComponent(ComponentInterface $component): self
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
     * @param ComponentInterface ...$components
     * @return $this
     */
    public function setForbiddenDependencyComponents(ComponentInterface ...$components): self
    {
        foreach ($components as $component) {
            $this->addForbiddenDependencyComponent($component);
        }
        return $this;
    }

    /**
     * @param ComponentInterface $component
     * @return $this
     */
    public function addForbiddenDependencyComponent(ComponentInterface $component): self
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
        $this->allowedState = $allowedState;
        return $this;
    }

    /**
     * @param float|null $maxAllowableDistance
     */
    public function setMaxAllowableDistance(?float $maxAllowableDistance): void
    {
        $this->maxAllowableDistance = $maxAllowableDistance;
    }

    /**
     * @param ComponentInterface $thisComponent
     * @return float
     */
    public function calculateDistanceRateOverage(ComponentInterface $thisComponent): float
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
     * @param ComponentInterface $dependency
     * @param ComponentInterface $thisComponent
     * @return bool
     */
    public function isDependencyAllowed(ComponentInterface $dependency, ComponentInterface $thisComponent): bool
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
     * @param ComponentInterface $dependencyComponent
     * @param ComponentInterface $thisComponent
     * @return bool
     */
    public function isComponentDependencyInAllowedState(ComponentInterface $dependencyComponent, ComponentInterface $thisComponent): bool
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
     * @param ComponentInterface $thisComponent
     * @return array<ComponentInterface>
     */
    public function getIllegalDependencyComponents(ComponentInterface $thisComponent): array
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
     * @param ComponentInterface $thisComponent
     * @param bool $onlyFromAllowedComponents
     * @return array<UnitOfCode>
     */
    public function getIllegalDependencyUnitsOfCode(ComponentInterface $thisComponent, bool $onlyFromAllowedComponents = false): array
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
