<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Type;

/**
 * Class TypeInterface
 * @package Chetkov\PHPCleanArchitecture\Model\Type
 */
class TypeInterface extends Type
{
    /**
     * @inheritDoc
     */
    public function isAbstract(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function isThisType(string $fullName): bool
    {
        return interface_exists($fullName);
    }
}
