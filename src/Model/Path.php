<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model;

use Chetkov\PHPCleanArchitecture\Service\Helper\PathHelper;

/**
 * Class Path
 * @package Chetkov\PHPCleanArchitecture\Model
 */
class Path
{
    /** @var string */
    private $path;

    /** @var string */
    private $namespace;

    /**
     * @param string $path
     * @param string $namespace
     */
    public function __construct(string $path, string $namespace = '')
    {
        $this->namespace = $namespace;
        $this->path = $path;
        if ($path) {
            $this->path = (string) realpath($path) ?: $path;
        }
    }

    /**
     * @param string $value directory path, filepath or namespace
     *
     * @return self
     */
    public static function fromString(string $value): self
    {
        if (class_exists($value) || trait_exists($value) || interface_exists($value)) {
            return new self((string) PathHelper::detectPath($value), $value);
        }

        if (file_exists($value) || is_dir($value)) {
            $value = realpath($value);
        }

        return new self($value);
    }

    /**
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function namespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $realPath
     * @return string
     */
    public function getRelativePath(string $realPath): string
    {
        return str_replace($this->path(), '', $realPath);
    }

    /**
     * @param string $fullPath
     * @return bool
     */
    public function isPartOfPath(string $fullPath): bool
    {
        return stripos($fullPath, $this->path()) === 0;
    }

    /**
     * @param string $namespace
     * @return bool
     */
    public function isPartOfNamespace(string $namespace): bool
    {
        return stripos($namespace, $this->namespace()) === 0;
    }

    /**
     * @param UnitOfCode $unitOfCode
     * @return bool
     */
    public function isContains(UnitOfCode $unitOfCode): bool
    {
        if ($unitOfCode->path() !== null) {
            return $this->isPartOfPath($unitOfCode->path());
        }

        return $this->isPartOfNamespace($unitOfCode->name());
    }
}
