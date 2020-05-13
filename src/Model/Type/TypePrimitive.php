<?php

namespace Chetkov\PHPCleanArchitecture\Model\Type;

/**
 * Class TypePrimitive
 * @package Chetkov\PHPCleanArchitecture\Model\Type
 */
class TypePrimitive extends Type
{
    private const EXISTING_PRIMITIVE_TYPES = [
        'int',
        'bool',
        'float',
        'string',
        'array',
        'object',
        'iterable',
        'callable',
        'resource',
        'integer',
        'boolean'
    ];

    private const EXISTING_PSEUDO_TYPES = [
        'mixed',
        'number',
        'callback',
        'void',
        'null'
    ];

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
    public static function isThisType(string $type): bool
    {
        return in_array($type, array_merge(
            self::EXISTING_PRIMITIVE_TYPES,
            self::EXISTING_PSEUDO_TYPES
        ),true);
    }
}
