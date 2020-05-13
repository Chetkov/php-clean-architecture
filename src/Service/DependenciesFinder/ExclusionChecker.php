<?php

namespace Chetkov\PHPCleanArchitecture\Service\DependenciesFinder;

/**
 * Class ExclusionChecker
 * @package Chetkov\PHPCleanArchitecture\Service\DependenciesFinder
 */
class ExclusionChecker
{
    public static function isExclusion(string $element): bool
    {
        return in_array($element, ['self', 'static', 'parent', 'void']);
    }
}
