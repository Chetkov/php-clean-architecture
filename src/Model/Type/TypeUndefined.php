<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Type;

/**
 * Class TypeUndefined
 * @package Chetkov\PHPCleanArchitecture\Model\Type
 */
class TypeUndefined extends Type
{
    /**
     * @inheritDoc
     */
    public function isAbstract(): ?bool
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public static function isThisType(string $fullName): bool
    {
        return !TypeInterface::isThisType($fullName)
            && !TypeClass::isThisType($fullName)
            && !TypeTrait::isThisType($fullName)
            && !TypePrimitive::isThisType($fullName);
    }
}
