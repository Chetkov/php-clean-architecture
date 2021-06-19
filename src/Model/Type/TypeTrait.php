<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Type;

/**
 * Class TypeTrait
 * @package Chetkov\PHPCleanArchitecture\Model\Type
 */
class TypeTrait extends Type
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
        return trait_exists($fullName);
    }
}
