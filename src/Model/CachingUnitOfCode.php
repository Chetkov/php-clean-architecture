<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model;

class CachingUnitOfCode extends UnitOfCode
{
    use CachingTrait;

//    /**
//     * @inheritDoc
//     */
//    public function belongToGlobalNamespace(): bool
//    {
//        return $this->execWithCache(__METHOD__, function () {
//            return parent::belongToGlobalNamespace();
//        });
//    }

    /**
     * @inheritDoc
     */
    public function isAccessibleFromOutside(): bool
    {
        return $this->execWithCache(__METHOD__, function () {
            return parent::isAccessibleFromOutside();
        });
    }

    /**
     * @inheritDoc
     */
    public function isDependencyInAllowedState(UnitOfCode $dependency): bool
    {
        $key = __METHOD__ . $dependency->name();
        return $this->execWithCache($key, function () use ($dependency) {
            return parent::isDependencyInAllowedState($dependency);
        });
    }

//    /**
//     * @inheritDoc
//     */
//    public function inputDependencies(?Component $component = null): array
//    {
//        if (!$component) {
//            return parent::inputDependencies();
//        }
//
//        $key = __METHOD__ . $component->name();
//        return $this->execWithCache($key, function () use ($component) {
//            return parent::inputDependencies($component);
//        });
//    }
//
//    /**
//     * @inheritDoc
//     */
//    public function outputDependencies(?Component $component = null): array
//    {
//        if (!$component) {
//            return parent::outputDependencies();
//        }
//
//        $key = __METHOD__ . $component->name();
//        return $this->execWithCache($key, function () use ($component) {
//            return parent::outputDependencies($component);
//        });
//    }

//    /**
//     * @inheritDoc
//     */
//    public function isAbstract(): ?bool
//    {
//        return $this->execWithCache(__METHOD__, function () {
//            return parent::isAbstract();
//        });
//    }
//
//    /**
//     * @inheritDoc
//     */
//    public function isPrimitive(): bool
//    {
//        return $this->execWithCache(__METHOD__, function () {
//            return parent::isPrimitive();
//        });
//    }
//
//    /**
//     * @inheritDoc
//     */
//    public function isClass(): bool
//    {
//        return $this->execWithCache(__METHOD__, function () {
//            return parent::isClass();
//        });
//    }
//
//    /**
//     * @inheritDoc
//     */
//    public function isInterface(): bool
//    {
//        return $this->execWithCache(__METHOD__, function () {
//            return parent::isInterface();
//        });
//    }
//
//    /**
//     * @inheritDoc
//     */
//    public function isTrait(): bool
//    {
//        return $this->execWithCache(__METHOD__, function () {
//            return parent::isTrait();
//        });
//    }
//
//    /**
//     * @inheritDoc
//     */
//    public function calculateInstabilityRate(): float
//    {
//        return $this->execWithCache(__METHOD__, function () {
//            return parent::calculateInstabilityRate();
//        });
//    }
//
//    /**
//     * @inheritDoc
//     */
//    public function calculatePrimitivenessRate(): float
//    {
//        return $this->execWithCache(__METHOD__, function () {
//            return parent::calculatePrimitivenessRate();
//        });
//    }
}
