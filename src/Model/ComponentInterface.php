<?php

namespace Chetkov\PHPCleanArchitecture\Model;

/**
 * Class Component
 * @package Chetkov\PHPCleanArchitecture\Model
 */
interface ComponentInterface
{
    /**
     * Проверяет, требуется-ли анализировать содержимое компонента?
     * @return bool
     */
    public function isEnabledForAnalysis(): bool;

    /**
     * Исключает метод из процесса анализа содержимого
     * @return $this
     */
    public function excludeFromAnalyze(): ComponentInterface;

    /**
     * Проверяет, является-ли переданный путь исключением?
     * Пример:
     *      если excludedPaths: ['/some/excluded/path'],
     *      то для значений $path:
     *          - '/some/excluded/path'
     *          - '/some/excluded/path/'
     *          - '/some/excluded/path/dir1/SomeClass.php'
     *          - '/some/excluded/path/dir2/...'
     *          - '/some/excluded/path/dir3/...'
     *          - и т.д.
     *      метод вернет true,
     * @param string $path
     * @return bool
     */
    public function isExcluded(string $path): bool;

    /**
     * @return bool
     */
    public function isUndefined(): bool;

    /**
     * @return bool
     */
    public function isGlobal(): bool;

    /**
     * @return bool
     */
    public function isPrimitives(): bool;

    /**
     * Возвращает название компонента
     * @return string
     */
    public function name(): string;

    /**
     * Возвращает пути корневых директорий компонента
     * @return array<Path>
     */
    public function rootPaths(): array;

    /**
     * Добавляет путь корневой директории компонента
     * @param Path $rootPath
     * @return $this
     */
    public function addRootPath(Path $rootPath): ComponentInterface;

    /**
     * Возвращает пути исключения
     * @return array<Path>
     */
    public function excludedPaths(): array;

    /**
     * Добавляет путь исключение
     * @param Path $excludedPath
     * @return $this
     */
    public function addExcludedPath(Path $excludedPath): ComponentInterface;

    /**
     * Проверяет, разрешена ли текщему компоненту зависимость от переданного?
     * @param ComponentInterface $dependency
     * @return bool
     */
    public function isDependencyAllowed(ComponentInterface $dependency): bool;

    /**
     * Проверяет, существует ли зависимость в конфиге разрешенного состояния
     * @param ComponentInterface $dependency
     * @return bool
     */
    public function isDependencyInAllowedState(ComponentInterface $dependency): bool;

    /**
     * @return Restrictions
     */
    public function restrictions(): Restrictions;

    /**
     * @param Restrictions $restrictions
     * @return ComponentInterface
     */
    public function setRestrictions(Restrictions $restrictions): ComponentInterface;

    /**
     * Возвращает список элементов компонента
     * @return array<UnitOfCode>
     */
    public function unitsOfCode(): array;

    /**
     * Добавляет элемент компонента
     * @param UnitOfCode $unitOfCode
     * @return $this
     */
    public function addUnitOfCode(UnitOfCode $unitOfCode): ComponentInterface;

    /**
     * Удаляет элемент компонента
     * @param UnitOfCode $unitOfCode
     * @return $this
     */
    public function removeUnitOfCode(UnitOfCode $unitOfCode): ComponentInterface;

    /**
     * Возвращает список компонентов, которые зависят от этого компонента.
     * @return array<ComponentInterface>
     */
    public function getDependentComponents(): array;

    /**
     * Возвращает список компонентов, от которых зависит этот компонент.
     * @return array<ComponentInterface>
     */
    public function getDependencyComponents(): array;

    /**
     * Возвращает список элементов этого компонента, которые зависят от элементов полученного компонента.
     * @param ComponentInterface $dependencyComponent
     * @return array<UnitOfCode>
     */
    public function getDependentUnitsOfCode(ComponentInterface $dependencyComponent): array;

    /**
     * Возвращает список элементов полученного компонента, от которых зависят элементы этого компонента.
     * @param ComponentInterface $dependencyComponent
     * @return array<UnitOfCode>
     */
    public function getDependencyUnitsOfCode(ComponentInterface $dependencyComponent): array;

    /**
     * Возвращает компоненты, от которых текущий зависеть не должен, но зависит
     * @return array<ComponentInterface>
     */
    public function getIllegalDependencyComponents(): array;

    /**
     * Возвращает элементы других компонентов, от которых текущий зависеть не должен, но зависит
     * @param bool $onlyFromAllowedComponents Если false, метод вернёт все запрещенные для взаимодействия элементы-зависимости,
     * т.е. элементы запрещенных для взаимодействия компонентов и приватные элементы разрешенных для взаимодействия компонентов.
     * Если true, метод вернет только запрещенные элементы-зависимости из разрешенных для взаимодействия компонентов,
     * т.е. только приватные элементы разрешенных для взаимодействия компонентов.
     * @return array<UnitOfCode>
     */
    public function getIllegalDependencyUnitsOfCode(bool $onlyFromAllowedComponents = false): array;

    /**
     * Возвращает найденные циклические зависимости компонентов
     * @param array<ComponentInterface> $path Оставь пустым (используется в рекурсии)
     * @param array<array<ComponentInterface>> $result Оставь пустым (используется в рекурсии)
     * @return array<array<ComponentInterface>>
     */
    public function getCyclicDependencies(array $path = [], array $result = []): array;

    /**
     * Рассчитывает абстрактность компонента <br>
     * A = Na ÷ Nc <br>
     * Где Na - число абстрактных элементов компонента, а Nc - общее число элементов компонента
     * @return float 0..1 (0 - полное отсутствие абстрактных элементов в компоненте, 1 - все элементы компонента абстрактны)
     */
    public function calculateAbstractnessRate(): float;

    /**
     * Рассчитывает неустойчивость компонента <br>
     * I = Fan-out ÷ (Fan-in + Fan-out) <br>
     * Где Fan-in - количество входящих зависимостей (классов вне данного компонента, которые зависят от классов внутри
     * компонента), а Fan-out - количество исходящих зависимостей (классов внутри данного компонента, зависящих от
     * классов за его пределами)
     * @return float 0..1 (0 - компонент максимально устойчив, 1 - компонент максимально неустойчив)
     */
    public function calculateInstabilityRate(): float;

    /**
     * Рассчитывает расстояние до главной последовательности на графике A/I <br>
     * D = |A+I–1| <br>
     * Где A - метрика абстрактности компонента, а I - метрика неустойчивости компонента
     * @return float
     * @see calculateInstabilityRate
     * @see calculateAbstractnessRate
     */
    public function calculateDistanceRate(): float;

    /**
     * Рассчитывает превышение метрикой D максимально допустимого значения (задаваемого в конфиге max_allowable_distance)
     * @return float
     */
    public function calculateDistanceRateOverage(): float;

    /**
     * Рассчитывает примитивность компонента
     * @return float
     */
    public function calculatePrimitivenessRate(): float;
}
