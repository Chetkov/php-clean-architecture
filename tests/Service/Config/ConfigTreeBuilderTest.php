<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Service\Config;

use Chetkov\PHPCleanArchitecture\Service\Config\ConfigTreeBuilder;
use Chetkov\PHPCleanArchitecture\Service\Config\EffectiveConfigNode;
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

    public function testChildListValuesOverrideInheritedListsInsteadOfBeingMergedByIndex(): void
    {
        $tree = (new ConfigTreeBuilder())->build([
            'reports_dir' => sys_get_temp_dir() . '/phpca-config-tree',
            'vendor_based_components' => [
                'enabled' => true,
                'vendor_path' => '/vendor',
                'excluded' => ['root-a', 'root-b'],
            ],
            'components' => [
                'Feature' => [
                    'roots' => [],
                    'sub' => [
                        'inherit' => ['vendor_based_components'],
                        'vendor_based_components' => [
                            'excluded' => ['child-only'],
                        ],
                        'components' => [],
                    ],
                ],
            ],
        ]);

        $child = $tree->children()[0];

        self::assertTrue($child->config()['vendor_based_components']['enabled']);
        self::assertSame('/vendor', $child->config()['vendor_based_components']['vendor_path']);
        self::assertSame(['child-only'], $child->config()['vendor_based_components']['excluded']);
    }

    public function testEmptySubConfigKeepsInheritedContextButEmptyChildListCanClearInheritedList(): void
    {
        $tree = (new ConfigTreeBuilder())->build([
            'reports_dir' => sys_get_temp_dir() . '/phpca-config-tree',
            'vendor_based_components' => [
                'enabled' => true,
                'vendor_path' => '/vendor',
                'excluded' => ['root-vendor'],
            ],
            'components' => [
                'Keeps Inherited Context' => [
                    'roots' => [],
                    'sub' => [
                        'inherit' => ['vendor_based_components'],
                        'components' => [],
                    ],
                ],
                'Clears Inherited List' => [
                    'roots' => [],
                    'sub' => [
                        'inherit' => ['vendor_based_components'],
                        'vendor_based_components' => [
                            'excluded' => [],
                        ],
                        'components' => [],
                    ],
                ],
            ],
        ]);

        self::assertSame(['root-vendor'], $tree->children()[0]->config()['vendor_based_components']['excluded']);
        self::assertSame([], $tree->children()[1]->config()['vendor_based_components']['excluded']);
    }

    public function testNormalizesDuplicateChildReportSlugsWithoutPathCollisions(): void
    {
        $reportsPath = sys_get_temp_dir() . '/phpca-config-tree';
        $tree = (new ConfigTreeBuilder())->build([
            'reports_dir' => $reportsPath,
            'components' => [
                'Sales API' => [
                    'roots' => [],
                    'sub' => ['components' => []],
                ],
                'Sales/API' => [
                    'roots' => [],
                    'sub' => ['components' => []],
                ],
                'Sales API!' => [
                    'roots' => [],
                    'sub' => ['components' => []],
                ],
            ],
        ]);

        self::assertSame(
            ['sales-api', 'sales-api-2', 'sales-api-3'],
            array_map(static function (EffectiveConfigNode $child): string {
                return $child->id();
            }, $tree->children())
        );
        self::assertSame(
            [
                $reportsPath . '/sales-api',
                $reportsPath . '/sales-api-2',
                $reportsPath . '/sales-api-3',
            ],
            array_map(static function (EffectiveConfigNode $child): string {
                return $child->reportPath();
            }, $tree->children())
        );
    }
}
