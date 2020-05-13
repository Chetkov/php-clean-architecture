<?php

namespace Chetkov\PHPCleanArchitecture\Model;

/**
 * Class Module
 * @package Chetkov\PHPCleanArchitecture\Model
 */
class Module
{
    private const UNDEFINED = '*undefined*';
    private const PRIMITIVES = '*primitives*';
    private const GLOBAL = '*global*';

    /** @var static[] */
    private static $instances = [];

    /** @var string */
    private $name;

    /** @var Path[] */
    private $rootPaths;

    /** @var Path[] */
    private $excludedPaths;

    /** @var UnitOfCode[] */
    private $publicUnitsOfCode = [];

    /** @var UnitOfCode[] */
    private $privateUnitsOfCode = [];

    /** @var Module[] */
    private $allowedDependencies = [];

    /** @var Module[] */
    private $forbiddenDependencies = [];

    /** @var UnitOfCode[] */
    private $unitsOfCode = [];

    /** @var float|null */
    private $maxAllowableDistance;

    /**
     * Module constructor.
     * @param string $name
     * @param Path[] $rootPaths
     * @param Path[] $excludedPaths
     * @param UnitOfCode[] $publicUnitsOfCode
     * @param UnitOfCode[] $privateUnitsOfCode
     * @param Module[] $allowedDependencies
     * @param Module[] $forbiddenDependencies
     * @param float|null $maxAllowableDistance
     */
    private function __construct(
        string $name,
        array $rootPaths,
        array $excludedPaths = [],
        array $publicUnitsOfCode = [],
        array $privateUnitsOfCode = [],
        array $allowedDependencies = [],
        array $forbiddenDependencies = [],
        ?float $maxAllowableDistance = null
    ) {
        $this->name = $name;
        $this->rootPaths = $rootPaths;
        $this->excludedPaths = $excludedPaths;
        foreach ($publicUnitsOfCode as $unitOfCode) {
            $this->addPublicUnitOfCode($unitOfCode);
        }
        foreach ($privateUnitsOfCode as $unitOfCode) {
            $this->addPrivateUnitOfCode($unitOfCode);
        }
        foreach ($allowedDependencies as $allowedDependency) {
            $this->addAllowedDependency($allowedDependency);
        }
        foreach ($forbiddenDependencies as $forbiddenDependency) {
            $this->addForbiddenDependency($forbiddenDependency);
        }
        $this->maxAllowableDistance = $maxAllowableDistance;
    }

    /**
     * @param string $name
     * @param Path[] $rootPaths
     * @param Path[] $excludedPaths
     * @param UnitOfCode[] $publicUnitsOfCode
     * @param UnitOfCode[] $privateUnitsOfCode
     * @param Module[] $allowedDependencies
     * @param Module[] $forbiddenDependencies
     * @param float|null $maxAllowableDistance
     * @return static
     */
    public static function create(
        string $name = self::UNDEFINED,
        array $rootPaths = [],
        array $excludedPaths = [],
        array $publicUnitsOfCode = [],
        array $privateUnitsOfCode = [],
        array $allowedDependencies = [],
        array $forbiddenDependencies = [],
        ?float $maxAllowableDistance = null
    ): self {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new static(
                $name,
                $rootPaths,
                $excludedPaths,
                $publicUnitsOfCode,
                $privateUnitsOfCode,
                $allowedDependencies,
                $forbiddenDependencies
            );
        }
        $module = self::$instances[$name];
        foreach ($rootPaths as $rootPath) {
            $module->addRootPath($rootPath);
        }
        foreach ($excludedPaths as $excludedPath) {
            $module->addExcludedPath($excludedPath);
        }
        foreach ($publicUnitsOfCode as $unitOfCode) {
            $module->addPublicUnitOfCode($unitOfCode);
        }
        foreach ($privateUnitsOfCode as $unitOfCode) {
            $module->addPrivateUnitOfCode($unitOfCode);
        }
        foreach ($allowedDependencies as $allowedDependency) {
            $module->addAllowedDependency($allowedDependency);
        }
        foreach ($forbiddenDependencies as $forbiddenDependency) {
            $module->addForbiddenDependency($forbiddenDependency);
        }
        if ($module->maxAllowableDistance === null) {
            $module->maxAllowableDistance = $maxAllowableDistance;
        }
        return $module;
    }

    /**
     * @param UnitOfCode $unitOfCode
     * @return static
     */
    public static function createByUnitOfCode(UnitOfCode $unitOfCode): self
    {
        if ($unitOfCode->isPrimitive()) {
            return self::create(self::PRIMITIVES);
        }

        if ($unitOfCode->belongToGlobalNamespace()) {
            return self::create(self::GLOBAL);
        }

        $isLocatedInOneOfPaths = static function (UnitOfCode $unitOfCode, Path ...$paths) {
            $trimmedUnitOfCodeName = trim($unitOfCode->name(), '\\');
            foreach ($paths as $path) {
                if ($path->namespace()) {
                    $trimmedNamespace = trim($path->namespace(), '\\');
                    if (stripos($trimmedUnitOfCodeName, $trimmedNamespace) === 0) {
                        return true;
                    }
                }
                if ($path->path() && stripos($unitOfCode->path(), $path->path()) === 0) {
                    return true;
                }
            }
            return false;
        };

        foreach (self::$instances as $existingModules) {
            if ($isLocatedInOneOfPaths($unitOfCode, ...$existingModules->rootPaths())
                && !$isLocatedInOneOfPaths($unitOfCode, ...$existingModules->excludedPaths())
            ) {
                return $existingModules;
            }
        }

        return self::create();
    }

    /**
     * @return Module[]
     */
    public static function getAll(): array
    {
        return self::$instances;
    }

    /**
     * @param string $name
     * @return Module|null
     */
    public static function findByName(string $name): ?Module
    {
        return self::$instances[$name] ?? null;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isExcluded(string $path): bool
    {
        foreach ($this->excludedPaths as $excludedPath) {
            if (stripos($path, $excludedPath->path()) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isUndefined(): bool
    {
        return $this->name === self::UNDEFINED;
    }

    /**
     * @return bool
     */
    public function isGlobal(): bool
    {
        return $this->name === self::GLOBAL;
    }

    /**
     * @return bool
     */
    public function isPrimitives(): bool
    {
        return $this->name === self::PRIMITIVES;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Path[]
     */
    public function rootPaths(): array
    {
        return $this->rootPaths;
    }

    /**
     * @param Path $rootPath
     * @return $this
     */
    public function addRootPath(Path $rootPath): self
    {
        if (!in_array($rootPath, $this->rootPaths, true)) {
            $this->rootPaths[] = $rootPath;
        }
        return $this;
    }

    /**
     * @return Path[]
     */
    public function excludedPaths(): array
    {
        return $this->excludedPaths;
    }

    /**
     * @param Path $excludedPath
     * @return $this
     */
    public function addExcludedPath(Path $excludedPath): self
    {
        if (!in_array($excludedPath, $this->excludedPaths, true)) {
            $this->excludedPaths[] = $excludedPath;
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
     * @param Module $allowedDependency
     * @return $this
     */
    public function addAllowedDependency(Module $allowedDependency): self
    {
        if (in_array($allowedDependency, $this->forbiddenDependencies, true)) {
            throw new \LogicException("Allowed dependency {$allowedDependency->name()} already added to forbidden list!");
        }
        if (!in_array($allowedDependency, $this->allowedDependencies, true)) {
            $this->allowedDependencies[] = $allowedDependency;
        }
        return $this;
    }

    /**
     * @param Module $forbiddenDependency
     * @return $this
     */
    public function addForbiddenDependency(Module $forbiddenDependency): self
    {
        if (in_array($forbiddenDependency, $this->allowedDependencies, true)) {
            throw new \LogicException("Forbidden dependency {$forbiddenDependency->name()} already added to allowed list!");
        }
        if (!in_array($forbiddenDependency, $this->forbiddenDependencies, true)) {
            $this->forbiddenDependencies[] = $forbiddenDependency;
        }
        return $this;
    }

    /**
     * @param Module $dependency
     * @return bool
     */
    public function isDependencyAllowed(Module $dependency): bool
    {
        if ($dependency === $this || $dependency->isPrimitives() || $dependency->isGlobal()) {
            return true;
        }
        if (empty($this->forbiddenDependencies)) {
            return empty($this->allowedDependencies) || in_array($dependency, $this->allowedDependencies, true);
        }
        if (empty($this->allowedDependencies)) {
            return empty($this->forbiddenDependencies) || !in_array($dependency, $this->forbiddenDependencies, true);
        }
        return in_array($dependency, $this->allowedDependencies, true)
            && !in_array($dependency, $this->forbiddenDependencies, true);
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
        if (!$unitOfCode->belongToModule($this)) {
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
     * Возвращает список элементов модуля.
     * @return UnitOfCode[]
     */
    public function unitsOfCode(): array
    {
        return $this->unitsOfCode;
    }

    /**
     * Добавляет элемент в модуль.
     * @param UnitOfCode $unitOfCode
     * @return $this
     */
    public function addUnitOfCode(UnitOfCode $unitOfCode): self
    {
        $this->unitsOfCode[spl_object_hash($unitOfCode)] = $unitOfCode;
        return $this;
    }

    /**
     * Удаляет элемент модуля.
     * @param UnitOfCode $unitOfCode
     * @return $this
     */
    public function removeUnitOfCode(UnitOfCode $unitOfCode): self
    {
        unset($this->unitsOfCode[spl_object_hash($unitOfCode)]);
        return $this;
    }

    /**
     * Возвращает список модулей, которые зависят от этого модуля.
     * @return Module[]
     */
    public function getDependentModules(): array
    {
        $uniqueDependentModules = [];
        foreach ($this->unitsOfCode as $unitOfCode) {
            foreach ($unitOfCode->inputDependencies() as $dependentUnitOfCode) {
                if (!$dependentUnitOfCode->belongToModule($this)) {
                    $module = $dependentUnitOfCode->module();
                    $uniqueDependentModules[spl_object_hash($module)] = $module;
                }
            }
        }
        return array_values($uniqueDependentModules);
    }

    /**
     * Возвращает список модулей, от которых зависит этот модуль.
     * @return Module[]
     */
    public function getDependencyModules(): array
    {
        $uniqueDependencyModules = [];
        foreach ($this->unitsOfCode as $unitOfCode) {
            foreach ($unitOfCode->outputDependencies() as $dependency) {
                if (!$dependency->belongToModule($this)) {
                    $module = $dependency->module();
                    $uniqueDependencyModules[spl_object_hash($module)] = $module;
                }
            }
        }
        return array_values($uniqueDependencyModules);
    }

    /**
     * Возвращает список элементов этого модуля, которые зависят от элементов полученного модуля.
     * @param Module $dependencyModule
     * @return UnitOfCode[]
     */
    public function getDependentUnitsOfCode(Module $dependencyModule): array
    {
        $uniqueDependentUnitsOfCode = [];
        foreach ($this->unitsOfCode as $unitOfCode) {
            foreach ($unitOfCode->outputDependencies() as $dependency) {
                if ($dependency->belongToModule($dependencyModule)) {
                    $uniqueDependentUnitsOfCode[spl_object_hash($unitOfCode)] = $unitOfCode;
                }
            }
        }
        return array_values($uniqueDependentUnitsOfCode);
    }

    /**
     * Возвращает список элементов полученного модуля, от которых зависят элементы этого модуля.
     * @param Module $dependencyModule
     * @return UnitOfCode[]
     */
    public function getDependencyUnitsOfCode(Module $dependencyModule): array
    {
        $uniqueDependencyUnitsOfCode = [];
        foreach ($this->unitsOfCode as $unitOfCode) {
            foreach ($unitOfCode->outputDependencies() as $dependency) {
                if ($dependency->belongToModule($dependencyModule)) {
                    $uniqueDependencyUnitsOfCode[spl_object_hash($dependency)] = $dependency;
                }
            }
        }
        return array_values($uniqueDependencyUnitsOfCode);
    }

    /**
     * @return Module[]
     */
    public function getIllegalDependencyModules(): array
    {
        $uniqueIllegalDependencyModules = [];
        foreach ($this->getDependencyModules() as $dependencyModule) {
            if (!$this->isDependencyAllowed($dependencyModule)) {
                $uniqueIllegalDependencyModules[spl_object_hash($dependencyModule)] = $dependencyModule;
            }
        }
        return array_values($uniqueIllegalDependencyModules);
    }

    /**
     * @param bool $onlyFromAllowedModules
     * @return UnitOfCode[]
     */
    public function getIllegalDependencyUnitsOfCode(bool $onlyFromAllowedModules = false): array
    {
        $uniqueIllegalDependencies = [];
        foreach ($this->unitsOfCode() as $unitOfCode) {
            foreach ($unitOfCode->outputDependencies() as $dependency) {
                if ($dependency->belongToModule($this)) {
                    continue;
                }

                $isDependencyAllowed = $this->isDependencyAllowed($dependency->module());
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

    /**
     * @param array $path
     * @param array $result
     * @return array
     */
    public function getCyclicDependencies(array $path = [], array $result = []): array
    {
        $path[] = $this;
        foreach ($this->getDependencyModules() as $dependencyModule) {
            if (in_array($dependencyModule, $path, true)) {
                if (isset($path[0]) && $path[0] === $dependencyModule) {
                    $result[] = array_merge($path, [$dependencyModule]);
                }
            } else {
                $result = $dependencyModule->getCyclicDependencies($path, $result);
            }
        }
        return $result;
    }

    /**
     * @return float
     */
    public function calculateAbstractnessRate(): float
    {
        $numOfConcrete = 0;
        $numOfAbstract = 0;
        foreach ($this->unitsOfCode as $unitOfCode) {
            $isAbstract = $unitOfCode->isAbstract();
            if ($isAbstract === true) {
                $numOfAbstract++;
            } elseif ($isAbstract === false) {
                $numOfConcrete++;
            }
        }

        $total = $numOfAbstract + $numOfConcrete;
        if ($total === 0) {
            return 0;
        }

        return round($numOfAbstract / $total, 3);
    }

    /**
     * @return float
     */
    public function calculateVariabilityRate(): float
    {
        $uniqueInputExternalDependencies = [];
        $uniqueOutputExternalDependencies = [];
        foreach ($this->unitsOfCode as $unitOfCode) {
            foreach ($unitOfCode->inputDependencies() as $dependency) {
                if (!$dependency->belongToModule($this)) {
                    $uniqueInputExternalDependencies[$dependency->name()] = true;
                }
            }
            foreach ($unitOfCode->outputDependencies() as $dependency) {
                if ($dependency->isPrimitive()) {
                    continue;
                }
                if (!$dependency->belongToModule($this)) {
                    $uniqueOutputExternalDependencies[$dependency->name()] = true;
                }
            }
        }
        
        $numOfUniqueInputExternalDependencies = count($uniqueInputExternalDependencies);
        $numOfUniqueOutputExternalDependencies = count($uniqueOutputExternalDependencies);
        $totalUniqueExternalDependencies = $numOfUniqueInputExternalDependencies + $numOfUniqueOutputExternalDependencies;

        return round($numOfUniqueOutputExternalDependencies / $totalUniqueExternalDependencies, 3);
    }

    /**
     * @return float|int
     */
    public function calculateDistanceRate()
    {
        return abs($this->calculateAbstractnessRate() + $this->calculateVariabilityRate() - 1);
    }

    /**
     * @return float
     */
    public function calculateDistanceRateOverage(): float
    {
        if ($this->maxAllowableDistance === null) {
            return 0;
        }

        $distanceRate = $this->calculateDistanceRate();
        return $distanceRate > $this->maxAllowableDistance
            ? $distanceRate - $this->maxAllowableDistance
            : 0;
    }

    /**
     * @return float
     */
    public function calculatePrimitivenessRate(): float
    {
        $sumPrimitivenessRates = 0;
        $numOfUnitOfCode = count($this->unitsOfCode);
        foreach ($this->unitsOfCode as $unitOfCode) {
            $sumPrimitivenessRates += $unitOfCode->calculatePrimitivenessRate();
        }

        if (!$numOfUnitOfCode) {
            return 0;
        }

        return round($sumPrimitivenessRates/$numOfUnitOfCode, 3);
    }
}
