<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Service\Analysis\DependenciesFinder;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\ReflectionDependenciesFinder;
use PHPUnit\Framework\TestCase;

final class ReflectionDependenciesFinderTest extends TestCase
{
    public function testReturnsNoDependenciesForNonReflectableExecutableUnit(): void
    {
        $finder = new ReflectionDependenciesFinder();

        self::assertSame([], $finder->find(UnitOfCode::create('phpca-check')));
    }
}
