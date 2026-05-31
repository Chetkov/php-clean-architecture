<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture;

use Chetkov\PHPCleanArchitecture\Service\Config\EffectiveConfigNode;
use Chetkov\PHPCleanArchitecture\Service\Report\History\ReportHistorySnapshotBuilder;
use Chetkov\PHPCleanArchitecture\Service\Report\History\ReportHistoryStorage;
use Chetkov\PHPCleanArchitecture\Service\Report\SpaReport\ReportSuiteRenderer;

final class ConfigTreeRunner
{
    /**
     * @return array<string, string>
     */
    public function allowCurrentState(EffectiveConfigNode $rootNode): array
    {
        $savedPaths = [];
        foreach ($rootNode->flatten() as $node) {
            $stateStorage = $node->config()['exclusions']['allowed_state']['storage'] ?? null;
            if (!is_string($stateStorage) || $stateStorage === '') {
                continue;
            }

            (new PHPCleanArchitectureFacade($node->config()))->allowCurrentState($stateStorage);
            $savedPaths[$node->id()] = $stateStorage;
        }

        if ($savedPaths === []) {
            throw new \RuntimeException('Config "exclusions.allowed_state.storage" must not be empty!');
        }

        return $savedPaths;
    }

    /**
     * @param array<string> $allowedPaths
     *
     * @return array<string>
     */
    public function check(EffectiveConfigNode $rootNode, array $allowedPaths = [], bool $recordHistory = false): array
    {
        $errors = [];
        foreach ($rootNode->flatten() as $node) {
            $nodeErrors = (new PHPCleanArchitectureFacade($node->config()))->check($allowedPaths);
            foreach ($nodeErrors as $error) {
                $errors[] = $node->id() === 'root' ? $error : '[' . $node->id() . '] ' . $error;
            }
        }

        if ($recordHistory || $this->shouldCollectHistoryOnCheck($rootNode)) {
            $this->recordHistoryFromAnalysis($rootNode, $allowedPaths);
        }

        return $errors;
    }

    /**
     * @param array<string> $allowedPaths
     */
    public function generateReports(EffectiveConfigNode $rootNode, array $allowedPaths = [], bool $recordHistory = false): void
    {
        foreach ($rootNode->flatten() as $node) {
            (new PHPCleanArchitectureFacade($node->config()))->generateReport($node->reportPath(), $allowedPaths);
        }

        $suiteRenderer = new ReportSuiteRenderer();
        $suiteData = $suiteRenderer->render($rootNode);

        if ($recordHistory || $this->isHistoryEnabled($rootNode)) {
            $historyData = $this->recordHistoryFromReports($rootNode, $suiteData);
            $suiteRenderer->writeHistory($rootNode, $historyData);
        }
    }

    /**
     * @param array<string> $scanPaths
     *
     * @return array<string, array<string>>
     */
    public function findUnmatchedFiles(EffectiveConfigNode $rootNode, array $scanPaths = []): array
    {
        $unmatchedFilesByNode = [];
        foreach ($rootNode->flatten() as $node) {
            $nodeScanPaths = $scanPaths === [] ? $this->defaultScanPaths($node) : $scanPaths;
            $unmatchedFiles = (new PHPCleanArchitectureFacade($node->config()))->findUnmatchedFiles($nodeScanPaths);
            if ($unmatchedFiles !== []) {
                $unmatchedFilesByNode[$node->id()] = $unmatchedFiles;
            }
        }

        return $unmatchedFilesByNode;
    }

    /**
     * @return array<string>
     */
    private function defaultScanPaths(EffectiveConfigNode $node): array
    {
        if (isset($node->config()['debug_scan_paths']) && is_array($node->config()['debug_scan_paths'])) {
            return array_values(array_filter($node->config()['debug_scan_paths'], static function ($path): bool {
                return is_string($path) && $path !== '';
            }));
        }

        $rootPaths = [];
        foreach ($node->config()['components'] as $componentConfig) {
            if (!is_array($componentConfig)) {
                continue;
            }

            foreach ($componentConfig['roots'] ?? [] as $rootConfig) {
                if (!is_array($rootConfig) || empty($rootConfig['path']) || !is_string($rootConfig['path'])) {
                    continue;
                }

                $rootPath = realpath($rootConfig['path']) ?: $rootConfig['path'];
                $rootPaths[] = is_file($rootPath) ? dirname($rootPath) : $rootPath;
            }
        }

        if ($rootPaths === []) {
            return [];
        }

        return [$this->commonParentPath($rootPaths)];
    }

    /**
     * @param array<string> $paths
     */
    private function commonParentPath(array $paths): string
    {
        $parts = array_map(static function (string $path): array {
            return explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
        }, $paths);

        $commonParts = [];
        $firstParts = array_shift($parts);
        if ($firstParts === null) {
            return $this->currentWorkingDirectory();
        }

        foreach ($firstParts as $index => $part) {
            foreach ($parts as $pathParts) {
                if (!isset($pathParts[$index]) || $pathParts[$index] !== $part) {
                    return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $commonParts);
                }
            }

            $commonParts[] = $part;
        }

        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $commonParts);
    }

    private function currentWorkingDirectory(): string
    {
        $currentWorkingDirectory = getcwd();
        if ($currentWorkingDirectory === false) {
            throw new \RuntimeException('Current working directory is not available.');
        }

        return $currentWorkingDirectory;
    }

    /**
     * @param array<string> $allowedPaths
     */
    private function recordHistoryFromAnalysis(EffectiveConfigNode $rootNode, array $allowedPaths): void
    {
        $reportDataByNodeId = [];
        foreach ($rootNode->flatten() as $node) {
            $reportDataByNodeId[$node->id()] = (new PHPCleanArchitectureFacade($node->config()))->buildReportData($allowedPaths);
        }

        $suiteData = $this->buildSuiteDataFromAnalysis($rootNode, $reportDataByNodeId);
        $rootReport = $reportDataByNodeId[$rootNode->id()];
        $this->recordHistorySnapshot($rootNode, $rootReport, $suiteData);
    }

    /**
     * @param array<string, mixed> $suiteData
     *
     * @return array<string, mixed>
     */
    private function recordHistoryFromReports(EffectiveConfigNode $rootNode, array $suiteData): array
    {
        $rootReport = $this->readReportData($rootNode->reportPath());

        return $this->recordHistorySnapshot($rootNode, $rootReport, $suiteData);
    }

    /**
     * @param array<string, mixed> $rootReport
     * @param array<string, mixed> $suiteData
     *
     * @return array<string, mixed>
     */
    private function recordHistorySnapshot(EffectiveConfigNode $rootNode, array $rootReport, array $suiteData): array
    {
        $historyDirectory = $this->historyDirectory($rootNode);
        $snapshot = (new ReportHistorySnapshotBuilder())->build($rootReport, $suiteData);

        return (new ReportHistoryStorage($historyDirectory))->append($snapshot);
    }

    /**
     * @param array<string, array<string, mixed>> $reportDataByNodeId
     *
     * @return array<string, mixed>
     */
    private function buildSuiteDataFromAnalysis(EffectiveConfigNode $rootNode, array $reportDataByNodeId): array
    {
        return [
            'schemaVersion' => 1,
            'rootId' => 'root',
            'tree' => $this->buildSuiteNodeFromAnalysis($rootNode, $rootNode->reportPath(), $reportDataByNodeId),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $reportDataByNodeId
     *
     * @return array<string, mixed>
     */
    private function buildSuiteNodeFromAnalysis(EffectiveConfigNode $node, string $rootReportPath, array $reportDataByNodeId): array
    {
        return [
            'id' => $node->id(),
            'title' => $node->title(),
            'reportPath' => $this->relativeReportPath($rootReportPath, $node->reportPath()),
            'report' => $reportDataByNodeId[$node->id()],
            'children' => array_map(function (EffectiveConfigNode $child) use ($rootReportPath, $reportDataByNodeId): array {
                return $this->buildSuiteNodeFromAnalysis($child, $rootReportPath, $reportDataByNodeId);
            }, $node->children()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readReportData(string $reportPath): array
    {
        $path = $reportPath . '/report.json';
        $json = file_get_contents($path);
        if (!is_string($json)) {
            throw new \RuntimeException(sprintf('Report data "%s" can not be read', $path));
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException(sprintf('Report data "%s" can not be decoded', $path));
        }

        return $data;
    }

    private function relativeReportPath(string $rootReportPath, string $reportPath): string
    {
        $rootReportPath = rtrim($rootReportPath, '/');
        $reportPath = rtrim($reportPath, '/');

        if ($reportPath === $rootReportPath) {
            return '.';
        }

        $prefix = $rootReportPath . '/';
        if (strpos($reportPath, $prefix) === 0) {
            return substr($reportPath, strlen($prefix));
        }

        return basename($reportPath);
    }

    private function isHistoryEnabled(EffectiveConfigNode $rootNode): bool
    {
        return !empty($rootNode->config()['history']['enabled']);
    }

    private function shouldCollectHistoryOnCheck(EffectiveConfigNode $rootNode): bool
    {
        return !empty($rootNode->config()['history']['enabled'])
            && !empty($rootNode->config()['history']['collect_on_check']);
    }

    private function historyDirectory(EffectiveConfigNode $rootNode): string
    {
        $directory = $rootNode->config()['history']['dir'] ?? null;
        if (is_string($directory) && $directory !== '') {
            return $directory;
        }

        return dirname($rootNode->reportPath()) . '/phpca-history';
    }
}
