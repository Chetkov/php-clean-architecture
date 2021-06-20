<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model;

class CachingComponent extends Component
{
    use CachingTrait;

    /**
     * @inheritDoc
     */
    public function isExcluded(string $path): bool
    {
        $key = __METHOD__ . $path;
        return $this->execWithCache($key, function () use ($path) {
            return parent::isExcluded($path);
        });
    }

    /**
     * @inheritDoc
     */
    public function isUndefined(): bool
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::isUndefined();
        });
    }

    /**
     * @inheritDoc
     */
    public function isGlobal(): bool
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::isGlobal();
        });
    }

    /**
     * @inheritDoc
     */
    public function isPrimitives(): bool
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::isPrimitives();
        });
    }

    /**
     * @inheritDoc
     */
    public function isDependencyAllowed(Component $dependency): bool
    {
        $key = __METHOD__ . $dependency->name();
        return $this->execWithCache($key, function () use ($dependency) {
            return parent::isDependencyAllowed($dependency);
        });
    }

    /**
     * @inheritDoc
     */
    public function isDependencyInAllowedState(Component $dependency): bool
    {
        $key = __METHOD__ . $dependency->name();
        return $this->execWithCache($key, function () use ($dependency) {
            return parent::isDependencyInAllowedState($dependency);
        });
    }

    /**
     * @inheritDoc
     */
    public function getDependentComponents(): array
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::getDependentComponents();
        });
    }

    /**
     * @inheritDoc
     */
    public function getDependencyComponents(): array
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::getDependencyComponents();
        });
    }

    /**
     * @inheritDoc
     */
    public function getDependentUnitsOfCode(Component $dependencyComponent): array
    {
        $key = __METHOD__ . $dependencyComponent->name();
        return $this->execWithCache($key, function () use ($dependencyComponent) {
            return parent::getDependentUnitsOfCode($dependencyComponent);
        });
    }

    /**
     * @inheritDoc
     */
    public function getDependencyUnitsOfCode(Component $dependencyComponent): array
    {
        $key = __METHOD__ . $dependencyComponent->name();
        return $this->execWithCache($key, function () use ($dependencyComponent) {
            return parent::getDependencyUnitsOfCode($dependencyComponent);
        });
    }

    /**
     * @inheritDoc
     */
    public function getIllegalDependencyComponents(): array
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::getIllegalDependencyComponents();
        });
    }

    /**
     * @inheritDoc
     */
    public function getIllegalDependencyUnitsOfCode(bool $onlyFromAllowedComponents = false): array
    {
        $key = __METHOD__ . (int) $onlyFromAllowedComponents;
        return $this->execWithCache($key, function () use ($onlyFromAllowedComponents) {
            return parent::getIllegalDependencyUnitsOfCode($onlyFromAllowedComponents);
        });
    }

    /**
     * @inheritDoc
     */
    public function getCyclicDependencies(array $path = [], array $result = []): array
    {
        $key = __METHOD__;
        return $this->execWithCache($key, function () use ($path, $result) {
            return parent::getCyclicDependencies($path, $result);
        });
    }

    /**
     * @inheritDoc
     */
    public function calculateAbstractnessRate(): float
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::calculateAbstractnessRate();
        });
    }

    /**
     * @inheritDoc
     */
    public function calculateInstabilityRate(): float
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::calculateInstabilityRate();
        });
    }

    /**
     * @inheritDoc
     */
    public function calculateDistanceRate(): float
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::calculateDistanceRate();
        });
    }

    /**
     * @inheritDoc
     */
    public function calculateDistanceRateOverage(): float
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::calculateDistanceRateOverage();
        });
    }

    /**
     * @inheritDoc
     */
    public function calculatePrimitivenessRate(): float
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::calculatePrimitivenessRate();
        });
    }
}
