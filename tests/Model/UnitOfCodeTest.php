<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Model;

use Chetkov\PHPCleanArchitecture\Model\AnalysisContext;
use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\Path;
use Chetkov\PHPCleanArchitecture\Model\SourceUnit;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use PHPUnit\Framework\TestCase;

final class UnitOfCodeTest extends TestCase
{
    public function testDeclaredSourceUnitUpgradesPreviouslyUnknownDependencyType(): void
    {
        $analysisContext = new AnalysisContext();
        $dependency = UnitOfCode::create($analysisContext, 'App\Future\UploadMediaAssetCommand');
        self::assertFalse($dependency->isClass());

        $component = Component::create($analysisContext, 'application', [
            new Path('/tmp/phpca-fixture-app', 'App\Future'),
        ]);

        $declaredUnit = UnitOfCode::createFromSourceUnit(
            $analysisContext,
            new SourceUnit(
                'App\Future\UploadMediaAssetCommand',
                '/tmp/phpca-fixture-app/UploadMediaAssetCommand.php',
                SourceUnit::KIND_CLASS
            ),
            $component
        );

        self::assertSame($dependency, $declaredUnit);
        self::assertTrue($declaredUnit->isClass());
        self::assertFalse($declaredUnit->isAbstract());
        self::assertSame('/tmp/phpca-fixture-app/UploadMediaAssetCommand.php', $declaredUnit->path());
        self::assertSame('application', $declaredUnit->component()->name());
    }
}
