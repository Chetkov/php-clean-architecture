<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Service;

use Chetkov\PHPCleanArchitecture\Model\AnalysisContext;
use Chetkov\PHPCleanArchitecture\Service\VendorBasedComponentsCreationService;
use PHPUnit\Framework\TestCase;

final class VendorBasedComponentsCreationServiceTest extends TestCase
{
    public function testCreatesVendorComponentFromClassmapFilesAndExcludes(): void
    {
        $vendorPath = __DIR__ . '/../Fixtures/VendorDiscovery/vendor';
        $analysisContext = new AnalysisContext();

        $components = (new VendorBasedComponentsCreationService([], $analysisContext))->create($vendorPath);

        $component = $analysisContext->componentByName('acme/classmap');
        self::assertNotNull($component);
        self::assertContains($component, $components);

        self::assertSame([
            realpath($vendorPath . '/acme/classmap/legacy/StrangeName.php'),
            realpath($vendorPath . '/acme/classmap/bootstrap/helpers.php'),
        ], array_map(static function ($path): string {
            return $path->path();
        }, $component->rootPaths()));

        self::assertSame([
            realpath($vendorPath . '/acme/classmap/legacy/Excluded'),
            realpath($vendorPath . '/acme/classmap/tests'),
        ], array_map(static function ($path): string {
            return $path->path();
        }, $component->excludedPaths()));

        self::assertSame('', $component->excludedPaths()[1]->namespace());
    }
}
