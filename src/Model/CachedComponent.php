<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model;

class CachedComponent implements ComponentInterface
{
    /** @var ComponentInterface */
    private $decorated;

    /** @var array<string, mixed> */
    private $cache;

    /**
     * @param ComponentInterface $decorated
     */
    public function __construct(ComponentInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    private function get(string $key)
    {
        return $this->cache[$key] ?? null;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    private function set(string $key, $value): void
    {
        $this->cache[$key] = $value;
    }

    /**
     * @param string $key
     * @param callable $callable
     * @return mixed
     */
    private function execWithCache(string $key, callable $callable)
    {
        $result = $this->get($key);
        if ($result === null) {
            $result = $callable();
            $this->set($key, $result);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function isEnabledForAnalysis(): bool
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->isEnabledForAnalysis();
        });
    }

    /**
     * @inheritDoc
     */
    public function excludeFromAnalyze(): ComponentInterface
    {
        $this->decorated->excludeFromAnalyze();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isExcluded(string $path): bool
    {
        $key = __METHOD__ . $path;
        return $this->execWithCache($key, function () use ($path) {
            return $this->decorated->isExcluded($path);
        });
    }

    /**
     * @inheritDoc
     */
    public function isUndefined(): bool
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->isUndefined();
        });
    }

    /**
     * @inheritDoc
     */
    public function isGlobal(): bool
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->isGlobal();
        });
    }

    /**
     * @inheritDoc
     */
    public function isPrimitives(): bool
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->isPrimitives();
        });
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->name();
        });
    }

    /**
     * @inheritDoc
     */
    public function rootPaths(): array
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->rootPaths();
        });
    }

    /**
     * @inheritDoc
     */
    public function addRootPath(Path $rootPath): ComponentInterface
    {
        $this->decorated->addRootPath($rootPath);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function excludedPaths(): array
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->excludedPaths();
        });
    }

    /**
     * @inheritDoc
     */
    public function addExcludedPath(Path $excludedPath): ComponentInterface
    {
        $this->decorated->addExcludedPath($excludedPath);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isDependencyAllowed(ComponentInterface $dependency): bool
    {
        $key = __METHOD__ . $dependency->name();
        return $this->execWithCache($key, function () use ($dependency) {
            return $this->decorated->isDependencyAllowed($dependency);
        });
    }

    /**
     * @inheritDoc
     */
    public function isDependencyInAllowedState(ComponentInterface $dependency): bool
    {
        $key = __METHOD__ . $dependency->name();
        return $this->execWithCache($key, function () use ($dependency) {
            return $this->decorated->isDependencyInAllowedState($dependency);
        });
    }

    /**
     * @inheritDoc
     */
    public function restrictions(): Restrictions
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->restrictions();
        });
    }

    /**
     * @inheritDoc
     */
    public function setRestrictions(Restrictions $restrictions): ComponentInterface
    {
        $this->decorated->setRestrictions($restrictions);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function unitsOfCode(): array
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->unitsOfCode();
        });
    }

    /**
     * @inheritDoc
     */
    public function addUnitOfCode(UnitOfCode $unitOfCode): ComponentInterface
    {
        $this->decorated->addUnitOfCode($unitOfCode);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function removeUnitOfCode(UnitOfCode $unitOfCode): ComponentInterface
    {
        $this->decorated->removeUnitOfCode($unitOfCode);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDependentComponents(): array
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->getDependentComponents();
        });
    }

    /**
     * @inheritDoc
     */
    public function getDependencyComponents(): array
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->getDependencyComponents();
        });
    }

    /**
     * @inheritDoc
     */
    public function getDependentUnitsOfCode(ComponentInterface $dependencyComponent): array
    {
        $key = __METHOD__ . $dependencyComponent->name();
        return $this->execWithCache($key, function () use ($dependencyComponent) {
            return $this->decorated->getDependentUnitsOfCode($dependencyComponent);
        });
    }

    /**
     * @inheritDoc
     */
    public function getDependencyUnitsOfCode(ComponentInterface $dependencyComponent): array
    {
        $key = __METHOD__ . $dependencyComponent->name();
        return $this->execWithCache($key, function () use ($dependencyComponent) {
            return $this->decorated->getDependencyUnitsOfCode($dependencyComponent);
        });
    }

    /**
     * @inheritDoc
     */
    public function getIllegalDependencyComponents(): array
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->getIllegalDependencyComponents();
        });
    }

    /**
     * @inheritDoc
     */
    public function getIllegalDependencyUnitsOfCode(bool $onlyFromAllowedComponents = false): array
    {
        $key = __METHOD__ . (int) $onlyFromAllowedComponents;
        return $this->execWithCache($key, function () use ($onlyFromAllowedComponents) {
            return $this->decorated->getIllegalDependencyUnitsOfCode($onlyFromAllowedComponents);
        });
    }

    /**
     * @inheritDoc
     */
    public function getCyclicDependencies(array $path = [], array $result = []): array
    {
        $key = __METHOD__ . json_encode($path) . json_encode($result);
        return $this->execWithCache($key, function () use ($path, $result) {
            return $this->decorated->getCyclicDependencies($path, $result);
        });
    }

    /**
     * @inheritDoc
     */
    public function calculateAbstractnessRate(): float
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->calculateAbstractnessRate();
        });
    }

    /**
     * @inheritDoc
     */
    public function calculateInstabilityRate(): float
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->calculateInstabilityRate();
        });
    }

    /**
     * @inheritDoc
     */
    public function calculateDistanceRate(): float
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->calculateDistanceRate();
        });
    }

    /**
     * @inheritDoc
     */
    public function calculateDistanceRateOverage(): float
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->calculateDistanceRateOverage();
        });
    }

    /**
     * @inheritDoc
     */
    public function calculatePrimitivenessRate(): float
    {
        return $this->execWithCache(__METHOD__, function () {
            return $this->decorated->calculatePrimitivenessRate();
        });
    }
}
