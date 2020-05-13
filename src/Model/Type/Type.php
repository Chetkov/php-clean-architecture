<?php

namespace Chetkov\PHPCleanArchitecture\Model\Type;

/**
 * Class Type
 * @package Chetkov\PHPCleanArchitecture\Model\Type
 */
abstract class Type
{
    /**
     * @var static[]
     */
    protected static $instances = [];

    /**
     * @return static
     */
    public static function getInstance(): self
    {
        $uniqueKey = sha1(static::class);
        if (!isset(self::$instances[$uniqueKey])) {
            self::$instances[$uniqueKey] = new static();
        }
        return self::$instances[$uniqueKey];
    }

    /**
     * @return bool|null
     */
    abstract public function isAbstract(): ?bool;

    /**
     * @param string $fullName
     * @return bool
     */
    abstract public static function isThisType(string $fullName): bool;
}
