<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Service\Analysis\DependenciesFinder\CodeParsing\Strategy;

use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesCalledStaticallyParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesCreatedThroughNewParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ClassesFromInstanceofConstructionParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\CodeParsingStrategyInterface;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\MethodAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ParamAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\PropertyAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ReturnAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\ThrowsAnnotationsParsingStrategy;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\CodeParsing\Strategy\VarAnnotationsParsingStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CodeParsingStrategiesTest extends TestCase
{
    /**
     * @param array<string> $expectedDependencies
     */
    #[DataProvider('strategyCases')]
    public function testParsesDependenciesSupportedByStrategy(
        CodeParsingStrategyInterface $strategy,
        string $content,
        array $expectedDependencies
    ): void {
        $dependencies = $strategy->parse($content);
        sort($dependencies);
        sort($expectedDependencies);

        self::assertSame($expectedDependencies, $dependencies);
    }

    /**
     * @return iterable<string, array{CodeParsingStrategyInterface, string, array<string>}>
     */
    public static function strategyCases(): iterable
    {
        yield 'new expressions' => [
            new ClassesCreatedThroughNewParsingStrategy(),
            '<?php $one = new App\Service\One(); $two = new \Vendor\Package\Two();',
            ['App\Service\One', '\Vendor\Package\Two'],
        ];

        yield 'static calls' => [
            new ClassesCalledStaticallyParsingStrategy(),
            '<?php App\Service\One::run(); \Vendor\Package\Two::run();',
            ['App\Service\One', '\Vendor\Package\Two'],
        ];

        yield 'instanceof expressions' => [
            new ClassesFromInstanceofConstructionParsingStrategy(),
            '<?php if ($value instanceof App\Service\One) {} if ($value instanceof \Vendor\Package\Two) {}',
            ['App\Service\One', '\Vendor\Package\Two'],
        ];

        yield 'property annotations' => [
            new PropertyAnnotationsParsingStrategy(),
            <<<'PHP'
<?php
/**
 * @property App\Service\One $one
 * @property-read App\Service\Two[] $two
 * @property-write App\Service\Three|App\Service\Four $mixed
 */
PHP,
            ['App\Service\Four', 'App\Service\One', 'App\Service\Three', 'App\Service\Two'],
        ];

        yield 'method annotations' => [
            new MethodAnnotationsParsingStrategy(),
            <<<'PHP'
<?php
/**
 * @method App\Service\ReturnType make(App\Service\Input $input, App\Service\Optional $optional = null): App\Service\RightType
 * @method static App\Service\StaticReturn create(App\Service\StaticInput $input): App\Service\StaticRight
 */
PHP,
            [
                'App\Service\Input',
                'App\Service\Optional',
                'App\Service\ReturnType',
                'App\Service\RightType',
                'App\Service\StaticInput',
                'App\Service\StaticReturn',
                'App\Service\StaticRight',
            ],
        ];

        yield 'param annotations' => [
            new ParamAnnotationsParsingStrategy(),
            <<<'PHP'
<?php
/**
 * @param App\Service\One|App\Service\Two[] $value
 */
PHP,
            ['App\Service\One', 'App\Service\Two'],
        ];

        yield 'return annotations' => [
            new ReturnAnnotationsParsingStrategy(),
            <<<'PHP'
<?php
/**
 * @return App\Service\One|App\Service\Two[]
 */
PHP,
            ['App\Service\One', 'App\Service\Two'],
        ];

        yield 'throws annotations' => [
            new ThrowsAnnotationsParsingStrategy(),
            <<<'PHP'
<?php
/**
 * @throws App\Service\FirstException|App\Service\SecondException
 */
PHP,
            ['App\Service\FirstException', 'App\Service\SecondException'],
        ];

        yield 'var annotations' => [
            new VarAnnotationsParsingStrategy(),
            <<<'PHP'
<?php
/** @var App\Service\One|App\Service\Two[] $value */
/** @var $other App\Service\Three */
PHP,
            ['App\Service\One', 'App\Service\Three', 'App\Service\Two'],
        ];
    }
}
