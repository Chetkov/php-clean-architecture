<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model;

final class AnalysisContext
{
    /** @var array<Component> */
    private $components = [];

    /** @var array<UnitOfCode> */
    private $unitsOfCode = [];

    public function componentByName(string $name): ?Component
    {
        return $this->components[$name] ?? null;
    }

    public function rememberComponent(Component $component): void
    {
        $this->components[$component->name()] = $component;
    }

    /**
     * @return array<Component>
     */
    public function components(): array
    {
        return $this->components;
    }

    public function unitOfCodeByName(string $name): ?UnitOfCode
    {
        return $this->unitsOfCode[$name] ?? null;
    }

    public function rememberUnitOfCode(UnitOfCode $unitOfCode): void
    {
        $this->unitsOfCode[$unitOfCode->name()] = $unitOfCode;
    }
}
