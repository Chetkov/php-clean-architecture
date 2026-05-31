<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Service\Report\History;

use Chetkov\PHPCleanArchitecture\Service\Report\History\ReportHistorySnapshotBuilder;
use PHPUnit\Framework\TestCase;

final class ReportHistorySnapshotBuilderTest extends TestCase
{
    public function testBuildCreatesLightweightRootSnapshot(): void
    {
        $rootReport = $this->reportData('2026-01-02T03:04:05+00:00', 'component-a');

        $snapshot = (new ReportHistorySnapshotBuilder())->build($rootReport);

        self::assertSame(1, $snapshot['schemaVersion']);
        self::assertStringStartsWith('2026-01-02t03-04-05-00-00-', $snapshot['id']);
        self::assertSame('2026-01-02T03:04:05+00:00', $snapshot['generatedAt']);
        self::assertCount(1, $snapshot['reports']);
        self::assertSame('root', $snapshot['reports'][0]['id']);
        self::assertSame('System', $snapshot['reports'][0]['title']);
        self::assertSame([], $snapshot['reports'][0]['path']);
        self::assertSame('.', $snapshot['reports'][0]['reportPath']);
        self::assertSame($rootReport['summary'], $snapshot['reports'][0]['summary']);
        self::assertSame('component-a', $snapshot['reports'][0]['components'][0]['id']);
        self::assertSame('component-a->component-b', $snapshot['reports'][0]['componentEdges'][0]['id']);
        self::assertArrayNotHasKey('units', $snapshot['reports'][0]);
        self::assertArrayNotHasKey('dependencies', $snapshot['reports'][0]);
        self::assertArrayHasKey('git', $snapshot['metadata']);
    }

    public function testBuildCreatesSuiteSnapshotForRootAndChildren(): void
    {
        $rootReport = $this->reportData('2026-01-02T03:04:05+00:00', 'component-root');
        $childReport = $this->reportData('2026-01-02T03:04:05+00:00', 'component-child');
        $suiteData = [
            'tree' => [
                'id' => 'root',
                'title' => 'System',
                'reportPath' => '.',
                'report' => $rootReport,
                'children' => [
                    [
                        'id' => 'component-a',
                        'title' => 'Component A',
                        'reportPath' => 'component-a',
                        'report' => $childReport,
                        'children' => [],
                    ],
                ],
            ],
        ];

        $snapshot = (new ReportHistorySnapshotBuilder())->build($rootReport, $suiteData);

        self::assertCount(2, $snapshot['reports']);
        self::assertSame('root', $snapshot['reports'][0]['id']);
        self::assertSame(['System'], $snapshot['reports'][0]['path']);
        self::assertSame('component-a', $snapshot['reports'][1]['id']);
        self::assertSame('Component A', $snapshot['reports'][1]['title']);
        self::assertSame(['System', 'Component A'], $snapshot['reports'][1]['path']);
        self::assertSame('component-a', $snapshot['reports'][1]['reportPath']);
        self::assertSame('component-child', $snapshot['reports'][1]['components'][0]['id']);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(string $generatedAt, string $componentId): array
    {
        return [
            'schemaVersion' => 4,
            'generatedAt' => $generatedAt,
            'summary' => [
                'components' => 1,
                'units' => 2,
                'dependencies' => 3,
                'violations' => 4,
                'activeViolations' => 5,
                'legacy' => [
                    'modernRate' => 0.42,
                ],
            ],
            'components' => [
                [
                    'id' => $componentId,
                    'name' => 'Component A',
                    'metrics' => [
                        'units' => 2,
                        'distance' => 0.1,
                    ],
                    'legacy' => [
                        'modernRate' => 0.42,
                    ],
                    'health' => [
                        'hasDistanceOverage' => false,
                    ],
                    'incomingComponentIds' => ['component-b'],
                    'outgoingComponentIds' => ['component-b'],
                ],
            ],
            'externalComponents' => [
                [
                    'id' => 'vendor',
                    'name' => 'Vendor',
                    'external' => true,
                ],
            ],
            'componentEdges' => [
                [
                    'id' => 'component-a->component-b',
                    'fromComponentId' => 'component-a',
                    'toComponentId' => 'component-b',
                    'weight' => 3,
                    'sourceUnitCount' => 2,
                    'targetUnitCount' => 1,
                    'counts' => [
                        'allowed' => 3,
                    ],
                    'status' => 'allowed',
                ],
            ],
            'units' => [
                ['id' => 'unit-a'],
            ],
            'dependencies' => [
                ['id' => 'dependency-a'],
            ],
        ];
    }
}
