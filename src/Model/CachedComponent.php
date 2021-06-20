<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model;

class CachedComponent extends Component
{
    /** @var array<string, mixed> */
    private $cache = [];

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
    public function calculateInstabilityRate(): float
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::calculateInstabilityRate();
        });
    }
}
