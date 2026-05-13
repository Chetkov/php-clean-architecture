<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Service\Config;

use Chetkov\PHPCleanArchitecture\Service\Config\ConfigTreeBuilder;
use PHPUnit\Framework\TestCase;

final class ConfigTreeBuilderTest extends TestCase
{
    public function testBuildsRecursiveTreeWithAccumulatedInheritanceAndNormalizedReportPaths(): void
    {
        $reportsPath = sys_get_temp_dir() . '/phpca-config-tree';
        $rootConfig = [
            'reports_dir' => $reportsPath,
            'vendor_based_components' => [
                'enabled' => false,
                'vendor_path' => '',
                'excluded' => ['root-vendor'],
            ],
            'restrictions' => [
                'check_acyclic_dependencies_principle' => false,
            ],
            'factories' => [
                'event_manager' => static function (): void {
                },
            ],
            'components' => [
                'Компонент A!' => [
                    'roots' => [],
                    'sub' => [
                        'inherit' => ['factories', 'vendor_based_components'],
                        'components' => [
                            'Layer 1' => [
                                'roots' => [],
                                'sub' => [
                                    'inherit' => ['factories', 'vendor_based_components', 'restrictions'],
                                    'components' => [
                                        [
                                            'name' => 'Deep Layer',
                                            'roots' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $tree = (new ConfigTreeBuilder())->build($rootConfig);

        self::assertSame('root', $tree->id());
        self::assertSame($reportsPath, $tree->reportPath());
        self::assertCount(1, $tree->children());

        $child = $tree->children()[0];
        self::assertSame('komponent-a', $child->id());
        self::assertSame($reportsPath . '/komponent-a', $child->reportPath());
        self::assertArrayHasKey('factories', $child->config());
        self::assertSame(['root-vendor'], $child->config()['vendor_based_components']['excluded']);
        self::assertSame([], $child->config()['restrictions']);

        $grandChild = $child->children()[0];
        self::assertSame('komponent-a/layer-1', $grandChild->id());
        self::assertSame($reportsPath . '/komponent-a/layer-1', $grandChild->reportPath());
        self::assertArrayHasKey('factories', $grandChild->config());
        self::assertSame(['root-vendor'], $grandChild->config()['vendor_based_components']['excluded']);
        self::assertSame([], $grandChild->config()['restrictions']);
    }
}
