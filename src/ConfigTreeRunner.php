<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture;

use Chetkov\PHPCleanArchitecture\Service\Config\EffectiveConfigNode;
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
    public function check(EffectiveConfigNode $rootNode, array $allowedPaths = []): array
    {
        $errors = [];
        foreach ($rootNode->flatten() as $node) {
            $nodeErrors = (new PHPCleanArchitectureFacade($node->config()))->check($allowedPaths);
            foreach ($nodeErrors as $error) {
                $errors[] = $node->id() === 'root' ? $error : '[' . $node->id() . '] ' . $error;
            }
        }

        return $errors;
    }

    /**
     * @param array<string> $allowedPaths
     */
    public function generateReports(EffectiveConfigNode $rootNode, array $allowedPaths = []): void
    {
        foreach ($rootNode->flatten() as $node) {
            (new PHPCleanArchitectureFacade($node->config()))->generateReport($node->reportPath(), $allowedPaths);
        }

        (new ReportSuiteRenderer())->render($rootNode);
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
}
