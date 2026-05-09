<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Service\Analysis\DependenciesFinder\Ast;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\Ast\AstDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\AttributeDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\CatchDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ClassConstantDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\DocBlockDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\FactoryDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\InstanceofDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\InterfaceDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\IntersectionDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\NestedDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ParamDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ParentDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\PromotedDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ReturnDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\SampleSubject;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ServiceDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\StaticDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ThrowsDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\TraitDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\UnionDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\VarDependency;
use PHPUnit\Framework\TestCase;

final class AstDependenciesFinderTest extends TestCase
{
    public function testFindsDependenciesSupportedByCurrentParser(): void
    {
        require_once __DIR__ . '/../../../../Fixtures/DependencyParsing/Dependencies.php';
        require_once __DIR__ . '/../../../../Fixtures/DependencyParsing/SampleSubject.php';

        $dependencies = self::find(SampleSubject::class, __DIR__ . '/../../../../Fixtures/DependencyParsing/SampleSubject.php');
        sort($dependencies);

        self::assertSame([
            DocBlockDependency::class,
            FactoryDependency::class,
            InstanceofDependency::class,
            ParamDependency::class,
            ReturnDependency::class,
            ServiceDependency::class,
            StaticDependency::class,
            ThrowsDependency::class,
            VarDependency::class,
        ], $dependencies);
    }

    public function testFindsModernAstDependenciesWithoutReflection(): void
    {
        $dependencies = self::find(
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ModernSubject',
            __DIR__ . '/../../../../Fixtures/DependencyParsing/ModernSubject.php'
        );
        sort($dependencies);

        self::assertSame([
            AttributeDependency::class,
            CatchDependency::class,
            ClassConstantDependency::class,
            DocBlockDependency::class,
            FactoryDependency::class,
            InstanceofDependency::class,
            InterfaceDependency::class,
            IntersectionDependency::class,
            NestedDependency::class,
            ParamDependency::class,
            ParentDependency::class,
            PromotedDependency::class,
            ReturnDependency::class,
            ServiceDependency::class,
            StaticDependency::class,
            ThrowsDependency::class,
            TraitDependency::class,
            UnionDependency::class,
            VarDependency::class,
        ], $dependencies);
    }

    public function testAnalyzesOnlyRequestedClassWhenFileContainsSeveralDeclarations(): void
    {
        $dependencies = self::find(
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\SecondSubject',
            __DIR__ . '/../../../../Fixtures/DependencyParsing/MultiSubject.php'
        );

        self::assertSame([ReturnDependency::class], $dependencies);
    }

    /**
     * @return array<string>
     */
    private static function find(string $unitOfCodeName, string $path): array
    {
        return (new AstDependenciesFinder())->find(UnitOfCode::create($unitOfCodeName, null, $path));
    }
}
