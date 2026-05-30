<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Service\Analysis;

use Chetkov\PHPCleanArchitecture\Model\AnalysisContext;
use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\Path;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Analysis\ComponentAnalyzer;
use Chetkov\PHPCleanArchitecture\Tests\Support\CapturingDependenciesFinder;
use Chetkov\PHPCleanArchitecture\Tests\Support\NullEventManager;
use PHPUnit\Framework\TestCase;

final class ComponentAnalyzerTest extends TestCase
{
    public function testUsesDeclaredSymbolsInsteadOfPsrPathMapping(): void
    {
        $dependenciesFinder = new CapturingDependenciesFinder();
        $analysisContext = new AnalysisContext();
        $component = Component::create(
            $analysisContext,
            'source-discovery-non-psr',
            [new Path(__DIR__ . '/../../Fixtures/SourceDiscovery/NonPsrLayout', 'Wrong\PathNamespace')]
        );

        $analyzer = new ComponentAnalyzer($dependenciesFinder, new NullEventManager(), $analysisContext);
        $analyzer->analyze($component);

        self::assertSame([
            'App\Domain\ActualClass',
            'App\Domain\ActualContract',
            'App\Domain\ActualTrait',
            'App\Domain\ActualStatus',
        ], self::unitNames($dependenciesFinder->unitsOfCode()));

        $unitsByName = [];
        foreach ($dependenciesFinder->unitsOfCode() as $unitOfCode) {
            $unitsByName[$unitOfCode->name()] = $unitOfCode;
        }

        self::assertTrue($unitsByName['App\Domain\ActualClass']->isClass());
        self::assertTrue($unitsByName['App\Domain\ActualClass']->isAbstract());
        self::assertTrue($unitsByName['App\Domain\ActualContract']->isInterface());
        self::assertTrue($unitsByName['App\Domain\ActualTrait']->isTrait());
        self::assertTrue($unitsByName['App\Domain\ActualStatus']->isClass());
    }

    public function testKeepsPathBasedFallbackForExecutableScriptsWithoutDeclaredSymbols(): void
    {
        $dependenciesFinder = new CapturingDependenciesFinder();
        $analysisContext = new AnalysisContext();
        $component = Component::create(
            $analysisContext,
            'source-discovery-scripts',
            [new Path(__DIR__ . '/../../Fixtures/SourceDiscovery/Scripts', 'Tools')]
        );

        $analyzer = new ComponentAnalyzer($dependenciesFinder, new NullEventManager(), $analysisContext);
        $analyzer->analyze($component);

        self::assertSame(['Tools\phpca-tool'], self::unitNames($dependenciesFinder->unitsOfCode()));
        self::assertSame('source-discovery-scripts', $dependenciesFinder->unitsOfCode()[0]->component()->name());
    }

    public function testMarksUnitsDiscoveredFromLegacyRootPath(): void
    {
        $dependenciesFinder = new CapturingDependenciesFinder();
        $analysisContext = new AnalysisContext();
        $component = Component::create(
            $analysisContext,
            'legacy-rate',
            [
                new Path(
                    __DIR__ . '/../../Fixtures/LegacyRateProject/Modern',
                    'Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Modern'
                ),
                new Path(
                    __DIR__ . '/../../Fixtures/LegacyRateProject/Legacy',
                    'Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Legacy',
                    true
                ),
            ]
        );

        $analyzer = new ComponentAnalyzer($dependenciesFinder, new NullEventManager(), $analysisContext);
        $analyzer->analyze($component);

        $legacyByName = [];
        $linesByName = [];
        foreach ($dependenciesFinder->unitsOfCode() as $unitOfCode) {
            $legacyByName[$unitOfCode->name()] = $unitOfCode->isLegacy();
            $linesByName[$unitOfCode->name()] = $unitOfCode->linesOfCode();
        }

        self::assertFalse($legacyByName['Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Modern\ModernService']);
        self::assertTrue($legacyByName['Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Legacy\LegacyService']);
        self::assertTrue($legacyByName['Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Legacy\LegacyHelper']);
        self::assertSame(
            self::countFixtureLines('/../../Fixtures/LegacyRateProject/Modern/ModernService.php'),
            $linesByName['Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Modern\ModernService']
        );
        self::assertSame(
            self::countFixtureLines('/../../Fixtures/LegacyRateProject/Legacy/LegacyService.php'),
            $linesByName['Chetkov\PHPCleanArchitecture\Tests\Fixtures\LegacyRateProject\Legacy\LegacyService']
        );
    }

    /**
     * @param array<UnitOfCode> $unitsOfCode
     * @return array<string>
     */
    private static function unitNames(array $unitsOfCode): array
    {
        return array_map(static function (UnitOfCode $unitOfCode): string {
            return $unitOfCode->name();
        }, $unitsOfCode);
    }

    private static function countFixtureLines(string $fixturePath): int
    {
        $lines = file(__DIR__ . $fixturePath);

        return is_array($lines) ? count($lines) : 0;
    }
}
