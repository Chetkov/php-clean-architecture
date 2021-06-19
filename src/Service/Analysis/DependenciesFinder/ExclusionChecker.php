<?php

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder;

/**
 * Class ExclusionChecker
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder
 */
class ExclusionChecker
{
    public static function isExclusion(string $element): bool
    {
        return in_array($element, ['self', 'static', 'parent', 'void']);
    }
}
