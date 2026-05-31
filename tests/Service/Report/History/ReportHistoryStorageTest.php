<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Tests\Service\Report\History;

use Chetkov\PHPCleanArchitecture\Service\Report\History\ReportHistoryStorage;
use PHPUnit\Framework\TestCase;

final class ReportHistoryStorageTest extends TestCase
{
    /** @var string */
    private $workspace;

    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir() . '/phpca-history-storage-test-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->workspace, 0777, true));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
    }

    public function testAppendWritesSnapshotsIndexAndEmbeddedHistoryInChronologicalOrder(): void
    {
        $storage = new ReportHistoryStorage($this->workspace);

        $storage->append($this->snapshot('snapshot-new', '2026-01-03T00:00:00+00:00', 3));
        $history = $storage->append($this->snapshot('snapshot-old', '2026-01-01T00:00:00+00:00', 1));

        self::assertFileExists($this->workspace . '/index.json');
        self::assertFileExists($this->workspace . '/snapshots/snapshot-new.json');
        self::assertFileExists($this->workspace . '/snapshots/snapshot-old.json');
        self::assertSame(1, $history['schemaVersion']);
        self::assertSame(['snapshot-old', 'snapshot-new'], array_column($history['index']['snapshots'], 'id'));
        self::assertSame(['snapshot-old', 'snapshot-new'], array_column($history['snapshots'], 'id'));
        self::assertSame(1, $history['index']['snapshots'][0]['summary']['components']);
        self::assertSame(3, $history['index']['snapshots'][1]['summary']['components']);
    }

    public function testAppendReplacesSnapshotWithSameId(): void
    {
        $storage = new ReportHistoryStorage($this->workspace);

        $storage->append($this->snapshot('snapshot', '2026-01-01T00:00:00+00:00', 1));
        $history = $storage->append($this->snapshot('snapshot', '2026-01-02T00:00:00+00:00', 2));

        self::assertCount(1, $history['index']['snapshots']);
        self::assertCount(1, $history['snapshots']);
        self::assertSame('2026-01-02T00:00:00+00:00', $history['snapshots'][0]['generatedAt']);
        self::assertSame(2, $history['index']['snapshots'][0]['summary']['components']);
    }

    public function testAppendRejectsUnsafeSnapshotId(): void
    {
        $storage = new ReportHistoryStorage($this->workspace);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('History snapshot id must be a non-empty file-safe string.');

        $storage->append($this->snapshot('../snapshot', '2026-01-01T00:00:00+00:00', 1));
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(string $id, string $generatedAt, int $components): array
    {
        return [
            'schemaVersion' => 1,
            'id' => $id,
            'generatedAt' => $generatedAt,
            'reports' => [
                [
                    'id' => 'root',
                    'summary' => [
                        'components' => $components,
                        'units' => 10,
                        'dependencies' => 20,
                        'violations' => 1,
                        'activeViolations' => 0,
                        'legacy' => [
                            'modernRate' => 0.5,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = glob($path . '/*');
        self::assertIsArray($files);
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}
