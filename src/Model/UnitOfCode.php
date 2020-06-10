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
     * Возвращает название элемента
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Возвращает путь к элементу
     * @return string|null
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * Устанавливает принадлежность элемента к переданному модулю
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
     * Возвращает модуль, которому элемент принадлежит
     * @return Module
     */
    public function module(): Module
    {
        return $this->module;
    }

    /**
     * Проверяет, располагается-ли элемент в глобальном namespace?
     * @return bool
     */
    public function belongToGlobalNamespace(): bool
    {
        return count(array_filter(explode('\\', $this->name))) === 1;
    }

    /**
     * Проверяет принадлежность элемента переланному модулю
     * @param Module $module
     * @return bool
     */
    public function belongToModule(Module $module): bool
    {
        return $this->module === $module;
    }

    /**
     * Проверяет, является-ли элемент доступным для взаимодействия извне модуля, к которому он принадлежит
     * @return bool
     */
    public function isAccessibleFromOutside(): bool
    {
        return $this->module->isUnitOfCodeAccessibleFromOutside($this);
    }

    /**
     * Возвращает массив входящих зависимостей (элементов, которые каким-то образом зависят от текущего)
     * @param Module|null $module Если передан, метод вернет только его зависимые элементы, иначе зависимые элементы
     * всех модулей
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
     * Добавляет связь с входящей зависимостью, одновременно устанавливает ей исходящую зависимость от текущего элемента
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
     * Возвращает массив исходящих зависимостей (элементов, от которых каким-то образом зависит текущий)
     * @param Module|null $module Если передан, метод вернет только его элементы, иначе элементы всех модулей
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
     * Добавляет связь с исходящей зависимостью, одновременно устанавливает ей входящую зависимость от текущего элемента
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
     * Является-ли элемент абстрактным?
     * @return bool|null Трэйты, примитивы и элементы, тип которых определить не удалось, не относятся ни к абстрактным,
     * ни к НЕ абстрактным, в этом случае метод вернёт null
     */
    public function isAbstract(): ?bool
    {
        return $this->type->isAbstract();
    }

    /**
     * Является-ли элемент примитивом?
     * @return bool
     */
    public function isPrimitive(): bool
    {
        return $this->type instanceof TypePrimitive;
    }

    /**
     * Является-ли элемент классом?
     * @return bool
     */
    public function isClass(): bool
    {
        return $this->type instanceof TypeClass;
    }

    /**
     * Является-ли элемент интерфейсом?
     * @return bool
     */
    public function isInterface(): bool
    {
        return $this->type instanceof TypeInterface;
    }

    /**
     * Является-ли элемент трэйтом?
     * @return bool
     */
    public function isTrait(): bool
    {
        return $this->type instanceof TypeTrait;
    }

    /**
     * Рассчитывает неустойчивость элемента <br>
     * I = Fan-out ÷ (Fan-in + Fan-out) <br>
     * Где Fan-in - количество входящих зависимостей (элементов зависящих от текущего), а Fan-out - количество исходящих
     * зависимостей (элементов, от которых зависит текущий)
     * @return float 0..1 (0 - элемент максимально устойчив, 1 - элемент максимально неустойчив)
     */
    public function calculateInstabilityRate(): float
    {
        $numOfOutputDependencies = count($this->outputDependencies);
        $numOfInputDependencies = count($this->inputDependencies);
        $total = $numOfInputDependencies + $numOfOutputDependencies;
        return $total === 0 ? 0 : round($numOfOutputDependencies / $total, 3);
    }

    /**
     * Рассчитывает примитивность элемента
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
