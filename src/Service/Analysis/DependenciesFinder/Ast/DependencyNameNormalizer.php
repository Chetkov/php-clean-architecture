<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\Ast;

use Chetkov\PHPCleanArchitecture\Model\Type\TypePrimitive;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\ExclusionChecker;

/**
 * Class DependencyNameNormalizer
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\Ast
 */
class DependencyNameNormalizer
{
    public function normalize(string $dependency): ?string
    {
        $dependency = trim($dependency, '\\');
        if (
            $dependency === ''
            || ExclusionChecker::isExclusion($dependency)
            || TypePrimitive::isThisType(strtolower($dependency))
        ) {
            return null;
        }

        return $dependency;
    }
}
