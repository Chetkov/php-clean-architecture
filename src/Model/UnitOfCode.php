<?php

namespace Chetkov\PHPCleanArchitecture\Model;

use Chetkov\PHPCleanArchitecture\Model\Type\Type;
use Chetkov\PHPCleanArchitecture\Model\Type\TypeClass;
use Chetkov\PHPCleanArchitecture\Model\Type\TypeInterface;
use Chetkov\PHPCleanArchitecture\Model\Type\TypePrimitive;
use Chetkov\PHPCleanArchitecture\Model\Type\TypeTrait;
use Chetkov\PHPCleanArchitecture\Model\Type\TypeUndefined;

/**
 * Class UnitOfCode
 * @package Chetkov\PHPCleanArchitecture\Model
 */
class UnitOfCode
{
    /** @var static[] */
    private static $instances = [];

    /** @var string */
    private $name;

    /** @var string|null */
    private $path;

    /** @var Type */
    private $type;

    /** @var UnitOfCode[] */
    private $inputDependencies = [];

    /** @var UnitOfCode[] */
    private $outputDependencies = [];

    /** @var Module */
    private $module;

    /**
     * UnitOfCode constructor.
     * @param string $name
     * @param Type $type
     * @param string|null $path
     * @param Module|null $module
     */
    private function __construct(string $name, Type $type, ?string $path = null, ?Module $module = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->path = $path;

        $module = $module ?? Module::createByUnitOfCode($this);
        $this->setModule($module);
    }

    /**
     * @param string $fullName
     * @param Module|null $module
     * @param string|null $path
     * @return static
     */
    public static function create(string $fullName, ?Module $module = null, ?string $path = null): UnitOfCode
    {
        $unitOfCode = self::$instances[$fullName] ?? null;
        if (!$unitOfCode) {
            $getElementPath = static function (string $fullName): ?string {
                try {
                    $reflection = new \ReflectionClass($fullName);
                    $path = $reflection->getFileName();
                } catch (\ReflectionException $e) {
                    $path = null;
                }
                return $path;
            };

            switch (true) {
                case TypeInterface::isThisType($fullName):
                    $type = TypeInterface::getInstance();
                    $path = $path ?? $getElementPath($fullName);
                    break;
                case TypeClass::isThisType($fullName):
                    try {
                        $reflection = new \ReflectionClass($fullName);
                        $isAbstract = $reflection->isAbstract();
                    } catch (\ReflectionException $e) {
                        $isAbstract = false;
                    }
                    $type = TypeClass::getInstance($isAbstract);
                    $path = $path ?? $getElementPath($fullName);
                    break;
                case TypeTrait::isThisType($fullName):
                    $type = TypeTrait::getInstance();
                    $path = $path ?? $getElementPath($fullName);
                    break;
                case TypePrimitive::isThisType($fullName):
                    $type = TypePrimitive::getInstance();
                    break;
                default:
                    $type = TypeUndefined::getInstance();
            }
            $unitOfCode = new UnitOfCode($fullName, $type, $path, $module);
            self::$instances[$fullName] = $unitOfCode;
        }
        if ($module) {
            $unitOfCode->setModule($module);
        }
        if ($path) {
            $unitOfCode->path = $path;
        }
        return $unitOfCode;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * @param Module $module
     * @return $this
     */
    private function setModule(Module $module): self
    {
        if ($this->module !== $module) {
            if ($this->module) {
                $this->module->removeUnitOfCode($this);
            }
            $module->addUnitOfCode($this);
            $this->module = $module;
        }
        return $this;
    }

    /**
     * @return Module
     */
    public function module(): Module
    {
        return $this->module;
    }

    /**
     * @return bool
     */
    public function belongToGlobalNamespace(): bool
    {
        return count(array_filter(explode('\\', $this->name))) === 1;
    }

    /**
     * @param Module $module
     * @return bool
     */
    public function belongToModule(Module $module): bool
    {
        return $this->module === $module;
    }

    /**
     * @return bool
     */
    public function isAccessibleFromOutside(): bool
    {
        return $this->module->isUnitOfCodeAccessibleFromOutside($this);
    }

    /**
     * @param Module|null $module
     * @return UnitOfCode[]
     */
    public function inputDependencies(?Module $module = null): array
    {
        if (!$module) {
            return $this->inputDependencies;
        }

        $inputDependencies = [];
        foreach ($this->inputDependencies as $dependency) {
            if ($dependency->belongToModule($module)) {
                $inputDependencies[] = $dependency;
            }
        }
        return $inputDependencies;
    }

    /**
     * @param UnitOfCode $unitOfCode
     * @return $this
     */
    public function addInputDependency(self $unitOfCode): self
    {
        $this->inputDependencies[] = $unitOfCode;
        if (!in_array($this, $unitOfCode->outputDependencies(), true)) {
            $unitOfCode->addOutputDependency($this);
        }
        return $this;
    }

    /**
     * @param Module|null $module
     * @return UnitOfCode[]
     */
    public function outputDependencies(?Module $module = null): array
    {
        if (!$module) {
            return $this->outputDependencies;
        }

        $outputDependencies = [];
        foreach ($this->outputDependencies as $dependency) {
            if ($dependency->belongToModule($module)) {
                $outputDependencies[] = $dependency;
            }
        }
        return $outputDependencies;
    }

    /**
     * @param UnitOfCode $unitOfCode
     * @return $this
     */
    public function addOutputDependency(self $unitOfCode): self
    {
        $this->outputDependencies[] = $unitOfCode;
        if (!in_array($this, $unitOfCode->inputDependencies(), true)) {
            $unitOfCode->addInputDependency($this);
        }
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isAbstract(): ?bool
    {
        return $this->type->isAbstract();
    }

    /**
     * @return bool
     */
    public function isPrimitive(): bool
    {
        return $this->type instanceof TypePrimitive;
    }

    public function isClass(): bool
    {
        return $this->type instanceof TypeClass;
    }

    public function isInterface(): bool
    {
        return $this->type instanceof TypeInterface;
    }

    public function isTrait(): bool
    {
        return $this->type instanceof TypeTrait;
    }

    /**
     * @return float
     */
    public function calculateInstabilityRate(): float
    {
        $numOfOutputDependencies = count($this->outputDependencies);
        $numOfInputDependencies = count($this->inputDependencies);
        $total = $numOfInputDependencies + $numOfOutputDependencies;
        return $total === 0 ? 0 : round($numOfOutputDependencies / $total, 3);
    }

    /**
     * @return float
     */
    public function calculatePrimitivenessRate(): float
    {
        if ($this->isPrimitive()) {
            return 1;
        }

        $numOfPrimitiveOutputDependencies = 0;
        $numOfOutputDependencies = count($this->outputDependencies());
        foreach ($this->outputDependencies() as $outputDependency) {
            if ($outputDependency->isPrimitive()) {
                $numOfPrimitiveOutputDependencies++;
            }
        }

        if (!$numOfOutputDependencies) {
            return 0;
        }

        return round($numOfPrimitiveOutputDependencies / $numOfOutputDependencies, 3);
    }
}
