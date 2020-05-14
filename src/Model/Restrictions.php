<?php

namespace Chetkov\PHPCleanArchitecture\Model;

/**
 * Class Restrictions
 * @package Chetkov\PHPCleanArchitecture\Model
 */
class Restrictions
{
    /** @var UnitOfCode[] */
    private $publicUnitsOfCode;

    /** @var UnitOfCode[] */
    private $privateUnitsOfCode;

    /** @var Module[] */
    private $allowedDependencyModules;

    /** @var Module[] */
    private $forbiddenDependencyModules;

    /** @var float|null */
    private $maxAllowableDistance;

    /**
     * Restrictions constructor.
     * @param UnitOfCode[] $publicUnitsOfCode
     * @param UnitOfCode[] $privateUnitsOfCode
     * @param Module[] $allowedDependencyModules
     * @param Module[] $forbiddenDependencyModules
     * @param float|null $maxAllowableDistance
     */
    public function __construct(
        array $publicUnitsOfCode = [],
        array $privateUnitsOfCode = [],
        array $allowedDependencyModules = [],
        array $forbiddenDependencyModules = [],
        ?float $maxAllowableDistance = null)
    {
        $this->setPublicUnitsOfCode(...$publicUnitsOfCode);
        $this->setPrivateUnitsOfCode(...$privateUnitsOfCode);
        $this->setAllowedDependencyModules(...$allowedDependencyModules);
        $this->setForbiddenDependencyModules(...$forbiddenDependencyModules);
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
        if (in_array($unitOfCode, $this->privateUnitsOfCode, true)) {
            throw new \LogicException("UnitOfCode {$unitOfCode->name()} already added to private list!");
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
        if (in_array($unitOfCode, $this->publicUnitsOfCode, true)) {
            throw new \LogicException("UnitOfCode {$unitOfCode->name()} already added to public list!");
        }
        if (!in_array($unitOfCode, $this->privateUnitsOfCode, true)) {
            $this->privateUnitsOfCode[] = $unitOfCode;
        }
        return $this;
    }

    /**
     * @param Module ...$modules
     * @return $this
     */
    public function setAllowedDependencyModules(Module ...$modules): self
    {
        foreach ($modules as $module) {
            $this->addAllowedDependencyModule($module);
        }
        return $this;
    }

    /**
     * @param Module $module
     * @return $this
     */
    public function addAllowedDependencyModule(Module $module): self
    {
        if (in_array($module, $this->forbiddenDependencyModules, true)) {
            throw new \LogicException("Allowed dependency {$module->name()} already added to forbidden list!");
        }
        if (!in_array($module, $this->allowedDependencyModules, true)) {
            $this->allowedDependencyModules[] = $module;
        }
        return $this;
    }

    /**
     * @param Module ...$modules
     * @return $this
     */
    public function setForbiddenDependencyModules(Module ...$modules): self
    {
        foreach ($modules as $module) {
            $this->addForbiddenDependencyModule($module);
        }
        return $this;
    }

    /**
     * @param Module $module
     * @return $this
     */
    public function addForbiddenDependencyModule(Module $module): self
    {
        if (in_array($module, $this->allowedDependencyModules, true)) {
            throw new \LogicException("Forbidden dependency {$module->name()} already added to allowed list!");
        }
        if (!in_array($module, $this->forbiddenDependencyModules, true)) {
            $this->forbiddenDependencyModules[] = $module;
        }
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
     * @param Module $thisModule
     * @return float
     */
    public function calculateDistanceRateOverage(Module $thisModule): float
    {
        if ($this->maxAllowableDistance === null) {
            return 0;
        }

        $distanceRate = $thisModule->calculateDistanceRate();
        return $distanceRate > $this->maxAllowableDistance
            ? $distanceRate - $this->maxAllowableDistance
            : 0;
    }

    /**
     * @param Module $dependency
     * @param Module $thisModule
     * @return bool
     */
    public function isDependencyAllowed(Module $dependency, Module $thisModule): bool
    {
        if ($dependency === $thisModule || $dependency->isPrimitives() || $dependency->isGlobal()) {
            return true;
        }
        if (empty($this->forbiddenDependencyModules)) {
            return empty($this->allowedDependencyModules) || in_array($dependency, $this->allowedDependencyModules, true);
        }
        if (empty($this->allowedDependencyModules)) {
            return empty($this->forbiddenDependencyModules) || !in_array($dependency, $this->forbiddenDependencyModules, true);
        }
        return in_array($dependency, $this->allowedDependencyModules, true)
            && !in_array($dependency, $this->forbiddenDependencyModules, true);
    }

    /**
     * @param UnitOfCode $unitOfCode
     * @param Module $thisModule
     * @return bool
     */
    public function isUnitOfCodeAccessibleFromOutside(UnitOfCode $unitOfCode, Module $thisModule): bool
    {
        if ($unitOfCode->isPrimitive() || $unitOfCode->belongToGlobalNamespace()) {
            return true;
        }
        if (!$unitOfCode->belongToModule($thisModule)) {
            throw new \InvalidArgumentException('$unitOfCode must belong to this module!');
        }
        if (empty($this->privateUnitsOfCode)) {
            return empty($this->publicUnitsOfCode) || in_array($unitOfCode, $this->publicUnitsOfCode, true);
        }
        if (empty($this->publicUnitsOfCode)) {
            return empty($this->privateUnitsOfCode) || !in_array($unitOfCode, $this->privateUnitsOfCode, true);
        }
        return in_array($unitOfCode, $this->publicUnitsOfCode, true)
            && !in_array($unitOfCode, $this->privateUnitsOfCode, true);
    }

    /**
     * @param Module $thisModule
     * @return Module[]
     */
    public function getIllegalDependencyModules(Module $thisModule): array
    {
        $uniqueIllegalDependencyModules = [];
        foreach ($thisModule->getDependencyModules() as $dependencyModule) {
            if (!$this->isDependencyAllowed($dependencyModule, $thisModule)) {
                $uniqueIllegalDependencyModules[spl_object_hash($dependencyModule)] = $dependencyModule;
            }
        }
        return array_values($uniqueIllegalDependencyModules);
    }

    /**
     * @param Module $thisModule
     * @param bool $onlyFromAllowedModules
     * @return UnitOfCode[]
     */
    public function getIllegalDependencyUnitsOfCode(Module $thisModule, bool $onlyFromAllowedModules = false): array
    {
        $uniqueIllegalDependencies = [];
        foreach ($thisModule->unitsOfCode() as $unitOfCode) {
            foreach ($unitOfCode->outputDependencies() as $dependency) {
                if ($dependency->belongToModule($thisModule)) {
                    continue;
                }

                $isDependencyAllowed = $this->isDependencyAllowed($dependency->module(), $thisModule);
                $isDependencyAccessibleFromOutside = $dependency->isAccessibleFromOutside();
                if ($onlyFromAllowedModules) {
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
