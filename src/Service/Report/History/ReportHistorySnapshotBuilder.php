<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Report\History;

final class ReportHistorySnapshotBuilder
{
    /**
     * @param array<string, mixed> $rootReport
     * @param array<string, mixed>|null $suiteData
     *
     * @return array<string, mixed>
     */
    public function build(array $rootReport, ?array $suiteData = null): array
    {
        $generatedAt = $this->generatedAt($rootReport);

        return [
            'schemaVersion' => 1,
            'id' => $this->snapshotId($generatedAt),
            'generatedAt' => $generatedAt,
            'metadata' => [
                'git' => $this->gitMetadata(),
            ],
            'reports' => $suiteData
                ? $this->suiteReports($suiteData, $rootReport)
                : [$this->reportSnapshot('root', 'System', [], '.', $rootReport)],
        ];
    }

    /**
     * @param array<string, mixed> $suiteData
     * @param array<string, mixed> $rootReport
     *
     * @return array<int, array<string, mixed>>
     */
    private function suiteReports(array $suiteData, array $rootReport): array
    {
        if (!isset($suiteData['tree']) || !is_array($suiteData['tree'])) {
            return [$this->reportSnapshot('root', 'System', [], '.', $rootReport)];
        }

        return $this->suiteNodeReports($suiteData['tree'], [], $rootReport);
    }

    /**
     * @param array<string, mixed> $node
     * @param array<int, string> $parentPath
     * @param array<string, mixed>|null $fallbackReport
     *
     * @return array<int, array<string, mixed>>
     */
    private function suiteNodeReports(array $node, array $parentPath, ?array $fallbackReport = null): array
    {
        $id = (string) ($node['id'] ?? 'root');
        $title = (string) ($node['title'] ?? $id);
        $path = array_merge($parentPath, [$title]);
        $report = isset($node['report']) && is_array($node['report']) ? $node['report'] : $fallbackReport;
        $reports = [];

        if ($report !== null) {
            $reports[] = $this->reportSnapshot(
                $id,
                $title,
                $path,
                (string) ($node['reportPath'] ?? '.'),
                $report
            );
        }

        foreach ($node['children'] ?? [] as $child) {
            if (!is_array($child)) {
                continue;
            }

            $reports = array_merge($reports, $this->suiteNodeReports($child, $path));
        }

        return $reports;
    }

    /**
     * @param array<int, string> $path
     * @param array<string, mixed> $report
     *
     * @return array<string, mixed>
     */
    private function reportSnapshot(string $id, string $title, array $path, string $reportPath, array $report): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'path' => $path,
            'reportPath' => $reportPath,
            'summary' => $report['summary'] ?? [],
            'components' => array_map(function (array $component): array {
                return $this->componentSnapshot($component);
            }, $report['components'] ?? []),
            'externalComponents' => $report['externalComponents'] ?? [],
            'componentEdges' => array_map(function (array $edge): array {
                return $this->componentEdgeSnapshot($edge);
            }, $report['componentEdges'] ?? []),
        ];
    }

    /**
     * @param array<string, mixed> $component
     *
     * @return array<string, mixed>
     */
    private function componentSnapshot(array $component): array
    {
        return [
            'id' => $component['id'] ?? null,
            'name' => $component['name'] ?? null,
            'metrics' => $component['metrics'] ?? [],
            'legacy' => $component['legacy'] ?? [],
            'health' => $component['health'] ?? [],
            'incomingComponentIds' => $component['incomingComponentIds'] ?? [],
            'outgoingComponentIds' => $component['outgoingComponentIds'] ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $edge
     *
     * @return array<string, mixed>
     */
    private function componentEdgeSnapshot(array $edge): array
    {
        return [
            'id' => $edge['id'] ?? null,
            'fromComponentId' => $edge['fromComponentId'] ?? null,
            'toComponentId' => $edge['toComponentId'] ?? null,
            'weight' => $edge['weight'] ?? 0,
            'sourceUnitCount' => $edge['sourceUnitCount'] ?? 0,
            'targetUnitCount' => $edge['targetUnitCount'] ?? 0,
            'counts' => $edge['counts'] ?? [],
            'status' => $edge['status'] ?? 'allowed',
        ];
    }

    /**
     * @param array<string, mixed> $rootReport
     */
    private function generatedAt(array $rootReport): string
    {
        $generatedAt = $rootReport['generatedAt'] ?? null;
        if (is_string($generatedAt) && $generatedAt !== '') {
            return $generatedAt;
        }

        return date(DATE_ATOM);
    }

    private function snapshotId(string $generatedAt): string
    {
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $generatedAt));
        return trim($slug, '-') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
    }

    /**
     * @return array<string, string|null>
     */
    private function gitMetadata(): array
    {
        return [
            'branch' => $this->gitValue('rev-parse --abbrev-ref HEAD'),
            'commit' => $this->gitValue('rev-parse HEAD'),
            'tag' => $this->gitValue('describe --tags --exact-match'),
        ];
    }

    private function gitValue(string $command): ?string
    {
        $output = [];
        $exitCode = 0;
        @exec('git ' . $command . ' 2>/dev/null', $output, $exitCode);
        if ($exitCode !== 0 || $output === []) {
            return null;
        }

        $value = trim((string) $output[0]);
        return $value === '' ? null : $value;
    }
}
