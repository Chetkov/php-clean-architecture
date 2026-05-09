<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Service\Analysis\DependenciesFinder\CodeParsing;

use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\CodeParsingDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesCalledStaticallyParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesCreatedThroughNewParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesFromInstanceofConstructionParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\MethodAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ParamAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\PropertyAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ReturnAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ThrowsAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\VarAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\DocBlockDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\FactoryDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\FullyQualifiedSubject;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\InstanceofDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ParamDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ReturnDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\SampleSubject;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ServiceDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\StaticDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\ThrowsDependency;
use Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\VarDependency;
use PHPUnit\Framework\TestCase;

final class CodeParsingDependenciesFinderTest extends TestCase
{
    public function testFindsDependenciesSupportedByCurrentRegexParser(): void
    {
        require_once __DIR__ . '/../../../../Fixtures/DependencyParsing/Dependencies.php';
        require_once __DIR__ . '/../../../../Fixtures/DependencyParsing/SampleSubject.php';

        self::assertFinderDependencies(SampleSubject::class);
    }

    public function testFindsFullyQualifiedDependenciesSupportedByCurrentRegexParser(): void
    {
        require_once __DIR__ . '/../../../../Fixtures/DependencyParsing/Dependencies.php';
        require_once __DIR__ . '/../../../../Fixtures/DependencyParsing/FullyQualifiedSubject.php';

        self::assertFinderDependencies(FullyQualifiedSubject::class);
    }

    private static function assertFinderDependencies(string $unitOfCodeName): void
    {
        $dependencies = self::createFinder()->find(UnitOfCode::create($unitOfCodeName));
        sort($dependencies);

        self::assertSame(self::expectedDependencies(), $dependencies);
    }

    private static function createFinder(): CodeParsingDependenciesFinder
    {
        return new CodeParsingDependenciesFinder(
            new ClassesCreatedThroughNewParsingStrategy(),
            new ClassesCalledStaticallyParsingStrategy(),
            new ClassesFromInstanceofConstructionParsingStrategy(),
            new PropertyAnnotationsParsingStrategy(),
            new MethodAnnotationsParsingStrategy(),
            new ParamAnnotationsParsingStrategy(),
            new ReturnAnnotationsParsingStrategy(),
            new ThrowsAnnotationsParsingStrategy(),
            new VarAnnotationsParsingStrategy()
        );
    }

    /**
     * @return array<string>
     */
    private static function expectedDependencies(): array
    {
        return [
            DocBlockDependency::class,
            FactoryDependency::class,
            InstanceofDependency::class,
            ParamDependency::class,
            ReturnDependency::class,
            ServiceDependency::class,
            StaticDependency::class,
            ThrowsDependency::class,
            VarDependency::class,
        ];
    }
}
