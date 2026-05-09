<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model;

/**
 * Class SourceUnit
 * @package Chetkov\PHPCleanArchitecture\Model
 */
class SourceUnit
{
    public const KIND_CLASS = 'class';
    public const KIND_INTERFACE = 'interface';
    public const KIND_TRAIT = 'trait';
    public const KIND_ENUM = 'enum';
    public const KIND_SCRIPT = 'script';

    /** @var string */
    private $name;

    /** @var string */
    private $path;

    /** @var string */
    private $kind;

    /** @var bool */
    private $isAbstract;

    public function __construct(string $name, string $path, string $kind, bool $isAbstract = false)
    {
        $this->name = trim($name, '\\');
        $this->path = $path;
        $this->kind = $kind;
        $this->isAbstract = $isAbstract;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }

    public function isDeclaredSymbol(): bool
    {
        return $this->kind !== self::KIND_SCRIPT;
    }
}
