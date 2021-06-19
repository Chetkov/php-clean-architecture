<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Type;

/**
 * Class Type
 * @package Chetkov\PHPCleanArchitecture\Model\Type
 */
abstract class Type
{
    /**
     * @var self[]
     */
    protected static $instances = [];

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        $uniqueKey = sha1(static::class);
        if (!isset(self::$instances[$uniqueKey])) {
            switch (static::class) {
                case TypeClass::class:
                    self::$instances[$uniqueKey] = new TypeClass();
                    break;
                case TypeInterface::class:
                    self::$instances[$uniqueKey] = new TypeInterface();
                    break;
                case TypeTrait::class:
                    self::$instances[$uniqueKey] = new TypeTrait();
                    break;
                case TypePrimitive::class:
                    self::$instances[$uniqueKey] = new TypePrimitive();
                    break;
                case TypeUndefined::class:
                    self::$instances[$uniqueKey] = new TypeUndefined();
                    break;
                default:
                    throw new \RuntimeException(sprintf('Unsupported type: %s', static::class));
            }
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
