<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Type;

/**
 * Class TypeClass
 * @package Chetkov\PHPCleanArchitecture\Model\Type
 */
class TypeClass extends Type
{
    /** @var bool */
    private $isAbstract;

    /**
     * @param bool $isAbstract
     */
    public function __construct(bool $isAbstract = false)
    {
        $this->isAbstract = $isAbstract;
    }

    /**
     * @param bool $isAbstract
     * @return Type
     */
    public static function getInstance(bool $isAbstract = false): Type
    {
        $uniqueKey = sha1(static::class . $isAbstract);
        if (!isset(self::$instances[$uniqueKey])) {
            self::$instances[$uniqueKey] = new static($isAbstract);
        }
        return self::$instances[$uniqueKey];
    }

    /**
     * @inheritDoc
     */
    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }

    /**
     * @inheritDoc
     */
    public static function isThisType(string $fullName): bool
    {
        return class_exists($fullName);
    }
}
