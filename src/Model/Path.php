<?php

namespace Chetkov\PHPCleanArchitecture\Model;

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
     * Path constructor.
     * @param string $path
     * @param string $namespace
     */
    public function __construct(string $path, string $namespace = '')
    {
        $this->path = (string) realpath($path);
        $this->namespace = $namespace;
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
    public function isPartOf(string $fullPath): bool
    {
        return stripos($fullPath, $this->path()) === 0;
    }
}
