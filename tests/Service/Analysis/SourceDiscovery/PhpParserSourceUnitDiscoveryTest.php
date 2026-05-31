<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Service\Analysis\SourceDiscovery;

use Chetkov\PHPCleanArchitecture\Model\Path;
use Chetkov\PHPCleanArchitecture\Model\SourceUnit;
use Chetkov\PHPCleanArchitecture\Service\Analysis\SourceDiscovery\PhpParserSourceUnitDiscovery;
use PHPUnit\Framework\TestCase;

final class PhpParserSourceUnitDiscoveryTest extends TestCase
{
    public function testDiscoversScriptFallbackFromEmptyNamespaceDirectoryRoot(): void
    {
        $path = __DIR__ . '/../../../Fixtures/SourceDiscovery/Scripts/phpca-tool';
        $sourceUnits = (new PhpParserSourceUnitDiscovery())->discover(
            new \SplFileInfo($path),
            new Path(__DIR__ . '/../../../Fixtures/SourceDiscovery/Scripts', '')
        );

        self::assertCount(1, $sourceUnits);
        self::assertSame('phpca-tool', $sourceUnits[0]->name());
        self::assertSame(SourceUnit::KIND_SCRIPT, $sourceUnits[0]->kind());
    }

    public function testSkipsNamelessFallbackFromExactEmptyNamespaceFileRoot(): void
    {
        $path = __DIR__ . '/../../../Fixtures/SourceDiscovery/Scripts/phpca-tool';
        $sourceUnits = (new PhpParserSourceUnitDiscovery())->discover(
            new \SplFileInfo($path),
            new Path($path, '')
        );

        self::assertSame([], $sourceUnits);
    }

    public function testDiscoversClassesFromPhp85SyntaxFiles(): void
    {
        $path = __DIR__ . '/../../../Fixtures/DependencyParsing/Php85Subject.php85';
        $sourceUnits = (new PhpParserSourceUnitDiscovery())->discover(
            new \SplFileInfo($path),
            new Path(__DIR__ . '/../../../Fixtures/DependencyParsing', 'Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing')
        );

        self::assertCount(1, $sourceUnits);
        self::assertSame(
            'Chetkov\PHPCleanArchitecture\Tests\Fixtures\DependencyParsing\Php85Subject',
            $sourceUnits[0]->name()
        );
        self::assertSame(SourceUnit::KIND_CLASS, $sourceUnits[0]->kind());
    }
}
