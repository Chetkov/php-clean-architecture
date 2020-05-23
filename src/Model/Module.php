<?php

namespace Chetkov\PHPCleanArchitecture\Model;

/**
 * Class Module
 * @package Chetkov\PHPCleanArchitecture\Model
 */
class Module
{
    /**
     * Название по умолчанию, если не передано другое
     */
    private const UNDEFINED = '*undefined*';

    /**
     * Название модуля, в который будут сложены используемые примитивы и псевдотипы
     */
    private const PRIMITIVES = '*primitives*';

    /**
     * Название модуля, в который будут сложены элементы относящиеся к глобальному namespace
     */
    private const GLOBAL = '*global*';

    /** @var static[] */
    private static $instances = [];

    /** @var string */
    private $name;

    /** @var Path[] */
    private $rootPaths;

    /** @var Path[] */
    private $excludedPaths;

    /** @var Restrictions */
    private $restrictions;

    /** @var UnitOfCode[] */
    private $unitsOfCode = [];

    /**
     * Module constructor.
     * @param string $name
     * @param Path[] $rootPaths
     * @param Path[] $excludedPaths
     * @param Restrictions|null $restrictions
     */
    private function __construct(
        string $name,
        array $rootPaths,
        array $excludedPaths = [],
        ?Restrictions $restrictions = null
    ) {
        $this->name = $name;
        $this->rootPaths = $rootPaths;
        $this->excludedPaths = $excludedPaths;
        $this->restrictions = $restrictions ?? new Restrictions();
    }

    /**
     * @param string $name
     * @param Path[] $rootPaths
     * @param Path[] $excludedPaths
     * @param Restrictions|null $restrictions
     * @return static
     */
    public static function create(
        string $name = self::UNDEFINED,
        array $rootPaths = [],
        array $excludedPaths = [],
        ?Restrictions $restrictions = null
    ): self {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new static(
                $name,
                $rootPaths,
                $excludedPaths,
                $restrictions
            );
        }
        $module = self::$instances[$name];
        foreach ($rootPaths as $rootPath) {
            $module->addRootPath($rootPath);
        }
        foreach ($excludedPaths as $excludedPath) {
            $module->addExcludedPath($excludedPath);
        }
        if ($restrictions) {
            $module->restrictions = $restrictions;
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
            if ($excludedPath->isPartOf($path)) {
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
     * @param Module $dependency
     * @return bool
     */
    public function isDependencyAllowed(Module $dependency): bool
    {
        return $this->restrictions->isDependencyAllowed($dependency, $this);
    }

    /**
     * @param UnitOfCode $unitOfCode
     * @return bool
     */
    public function isUnitOfCodeAccessibleFromOutside(UnitOfCode $unitOfCode): bool
    {
        return $this->restrictions->isUnitOfCodeAccessibleFromOutside($unitOfCode, $this);
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
                if (!$dependency->belongToModule($this)
                    && !$dependency->belongToGlobalNamespace()
                    && !$dependency->isPrimitive()
                ) {
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
        return $this->restrictions->getIllegalDependencyModules($this);
    }

    /**
     * @param bool $onlyFromAllowedModules
     * @return UnitOfCode[]
     */
    public function getIllegalDependencyUnitsOfCode(bool $onlyFromAllowedModules = false): array
    {
        return $this->restrictions->getIllegalDependencyUnitsOfCode($this, $onlyFromAllowedModules);
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
    public function calculateInstabilityRate(): float
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
                if ($dependency->belongToModule($this)
                    || $dependency->belongToGlobalNamespace()
                    || $dependency->isPrimitive()
                ) {
                    continue;
                }
                $uniqueOutputExternalDependencies[$dependency->name()] = true;
            }
        }
        
        $numOfUniqueInputExternalDependencies = count($uniqueInputExternalDependencies);
        $numOfUniqueOutputExternalDependencies = count($uniqueOutputExternalDependencies);
        $totalUniqueExternalDependencies = $numOfUniqueInputExternalDependencies + $numOfUniqueOutputExternalDependencies;

        return $totalUniqueExternalDependencies ?
            round($numOfUniqueOutputExternalDependencies / $totalUniqueExternalDependencies, 3)
            : 0;
    }

    /**
     * @return float|int
     */
    public function calculateDistanceRate()
    {
        return abs($this->calculateAbstractnessRate() + $this->calculateInstabilityRate() - 1);
    }

    /**
     * @return float
     */
    public function calculateDistanceRateOverage(): float
    {
        return $this->restrictions->calculateDistanceRateOverage($this);
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
