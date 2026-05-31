<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\History;

final class ReportHistoryStorage
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/');
    }

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    public function append(array $snapshot): array
    {
        $snapshotId = $this->snapshotId($snapshot);
        $this->ensureDirectoryExists($this->snapshotsDirectory());
        $snapshotFile = $this->snapshotsDirectory() . '/' . $snapshotId . '.json';
        $this->writeJson($snapshotFile, $snapshot);

        $index = $this->readIndex();
        $index['snapshots'] = array_values(array_filter(
            $index['snapshots'] ?? [],
            static function ($item) use ($snapshotId): bool {
                return !is_array($item) || ($item['id'] ?? null) !== $snapshotId;
            }
        ));
        $index['snapshots'][] = $this->snapshotIndexItem($snapshot);
        usort($index['snapshots'], static function (array $left, array $right): int {
            return strcmp((string) ($left['generatedAt'] ?? ''), (string) ($right['generatedAt'] ?? ''));
        });
        $index['updatedAt'] = date(DATE_ATOM);
        $this->writeJson($this->indexPath(), $index);

        return $this->readEmbeddedHistory();
    }

    /**
     * @return array<string, mixed>
     */
    public function readEmbeddedHistory(): array
    {
        $index = $this->readIndex();
        $snapshots = [];
        foreach ($index['snapshots'] ?? [] as $item) {
            if (!is_array($item) || empty($item['file']) || !is_string($item['file'])) {
                continue;
            }

            $snapshot = $this->readJson($this->directory . '/' . $item['file']);
            if ($snapshot !== null) {
                $snapshots[] = $snapshot;
            }
        }

        return [
            'schemaVersion' => 1,
            'index' => $index,
            'snapshots' => $snapshots,
        ];
    }

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    private function snapshotIndexItem(array $snapshot): array
    {
        $snapshotId = $this->snapshotId($snapshot);
        $firstReport = $snapshot['reports'][0] ?? [];
        $summary = is_array($firstReport) ? ($firstReport['summary'] ?? []) : [];

        return [
            'id' => $snapshotId,
            'generatedAt' => $this->generatedAt($snapshot),
            'file' => 'snapshots/' . $snapshotId . '.json',
            'summary' => [
                'components' => $summary['components'] ?? 0,
                'units' => $summary['units'] ?? 0,
                'dependencies' => $summary['dependencies'] ?? 0,
                'violations' => $summary['violations'] ?? 0,
                'activeViolations' => $summary['activeViolations'] ?? 0,
                'legacy' => $summary['legacy'] ?? [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    private function snapshotId(array $snapshot): string
    {
        $id = $snapshot['id'] ?? null;
        if (!is_string($id) || $id === '' || $id !== basename($id)) {
            throw new \RuntimeException('History snapshot id must be a non-empty file-safe string.');
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    private function generatedAt(array $snapshot): string
    {
        $generatedAt = $snapshot['generatedAt'] ?? null;
        if (!is_string($generatedAt) || $generatedAt === '') {
            throw new \RuntimeException('History snapshot generatedAt must be a non-empty string.');
        }

        return $generatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    private function readIndex(): array
    {
        $index = $this->readJson($this->indexPath());
        if ($index === null) {
            return [
                'schemaVersion' => 1,
                'createdAt' => date(DATE_ATOM),
                'updatedAt' => date(DATE_ATOM),
                'snapshots' => [],
            ];
        }

        return $index;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $json = file_get_contents($path);
        if (!is_string($json)) {
            throw new \RuntimeException(sprintf('History file "%s" can not be read.', $path));
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException(sprintf('History file "%s" can not be decoded.', $path));
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeJson(string $path, array $data): void
    {
        $this->ensureDirectoryExists(dirname($path));
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            throw new \RuntimeException('History data can not be encoded to JSON.');
        }

        if (file_put_contents($path, $json . PHP_EOL) === false) {
            throw new \RuntimeException(sprintf('History file "%s" was not written.', $path));
        }
    }

    private function indexPath(): string
    {
        return $this->directory . '/index.json';
    }

    private function snapshotsDirectory(): string
    {
        return $this->directory . '/snapshots';
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }
}
