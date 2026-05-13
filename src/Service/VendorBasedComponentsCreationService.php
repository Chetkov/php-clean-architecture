<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service;

use Chetkov\PHPCleanArchitecture\Model\Helper\PathHelper;
use Chetkov\PHPCleanArchitecture\Model\AnalysisContext;
use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\Path;

/**
 * Class VendorBasedComponentsCreationService
 * @package Chetkov\PHPCleanArchitecture\Service
 */
class VendorBasedComponentsCreationService
{
    /** @var array<string> */
    private $excludedPaths;

    /** @var AnalysisContext */
    private $context;

    /**
     * @param array<string> $excludedPaths
     */
    public function __construct(array $excludedPaths, AnalysisContext $context)
    {
        $this->excludedPaths = $excludedPaths;
        $this->context = $context;
    }

    /**
     * @param string $pathToVendor
     * @return array<Component>
     */
    public function create(string $pathToVendor): array
    {
        $components = [];
        $composerFiles = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pathToVendor)), '/composer.json/i');

        /** @var \SplFileInfo $composerFile */
        foreach ($composerFiles as $composerFile) {
            $filePath = $composerFile->getRealPath();
            if (!$filePath || $this->isExcludedPath($filePath)) {
                continue;
            }

            if (!$content = file_get_contents($filePath)) {
                continue;
            }

            $composerData = json_decode($content, true);
            if (json_last_error() !== 0) {
                continue;
            }

            $packageName = $composerData['name'] ?? null;
            if (!$packageName) {
                continue;
            }

            $autoloadSection = $composerData['autoload'] ?? [];
            $rootPaths = $this->createRootPathsByAutoloadSection($autoloadSection, $composerFile->getPath());

            $autoloadDevSection = $composerData['autoload-dev'] ?? [];
            $excludedPaths = $this->createExcludedPathsByAutoloadSection(
                $autoloadDevSection,
                $composerData['exclude-from-classmap'] ?? [],
                $composerFile->getPath()
            );

            $components[] = Component::create($this->context, $packageName, $rootPaths, $excludedPaths)
                ->excludeFromAnalyze();
        }

        return $components;
    }

    /**
     * @param array<string, mixed> $autoloadSection
     * @param string $currentPath
     * @return array<Path>
     */
    private function createRootPathsByAutoloadSection(array $autoloadSection, string $currentPath): array
    {
        $rootPaths = [];
        $psr4 = $autoloadSection['psr-4'] ?? [];
        $psr0 = $autoloadSection['psr-0'] ?? [];
        $rootPaths = array_merge($rootPaths, $this->createPsrPaths($psr4, $currentPath));
        $rootPaths = array_merge($rootPaths, $this->createPsrPaths($psr0, $currentPath));

        $classmap = $autoloadSection['classmap'] ?? [];
        $files = $autoloadSection['files'] ?? [];
        $rootPaths = array_merge($rootPaths, $this->createPlainPaths($classmap, $currentPath));
        $rootPaths = array_merge($rootPaths, $this->createPlainPaths($files, $currentPath));

        return $rootPaths;
    }

    /**
     * @param array<string, mixed> $autoloadDevSection
     * @param mixed $excludeFromClassmap
     * @param string $currentPath
     * @return array<Path>
     */
    private function createExcludedPathsByAutoloadSection(
        array $autoloadDevSection,
        $excludeFromClassmap,
        string $currentPath
    ): array {
        $excludedPaths = $this->createPlainPaths($excludeFromClassmap, $currentPath);

        $psr4 = $autoloadDevSection['psr-4'] ?? [];
        $psr0 = $autoloadDevSection['psr-0'] ?? [];
        $classmap = $autoloadDevSection['classmap'] ?? [];
        $files = $autoloadDevSection['files'] ?? [];

        $excludedPaths = array_merge($excludedPaths, $this->createPsrPaths($psr4, $currentPath, false));
        $excludedPaths = array_merge($excludedPaths, $this->createPsrPaths($psr0, $currentPath, false));
        $excludedPaths = array_merge($excludedPaths, $this->createPlainPaths($classmap, $currentPath));
        $excludedPaths = array_merge($excludedPaths, $this->createPlainPaths($files, $currentPath));

        return $excludedPaths;
    }

    /**
     * @param mixed $autoloadPaths
     * @param string $currentPath
     * @return array<Path>
     */
    private function createPlainPaths($autoloadPaths, string $currentPath): array
    {
        $rootPaths = [];
        foreach ($this->normalizeRelativePaths($autoloadPaths) as $relativeRootPath) {
            $rootPaths[] = new Path($this->createFullPath($currentPath, $relativeRootPath));
        }
        return $rootPaths;
    }

    /**
     * @param mixed $autoloadPathsByNamespace
     * @param string $currentPath
     * @param bool $includeNamespace
     * @return array<Path>
     */
    private function createPsrPaths($autoloadPathsByNamespace, string $currentPath, bool $includeNamespace = true): array
    {
        if (!is_array($autoloadPathsByNamespace)) {
            return [];
        }

        $rootPaths = [];
        foreach ($autoloadPathsByNamespace as $namespace => $relativeRootPaths) {
            if (!is_array($relativeRootPaths)) {
                 $relativeRootPaths = [$relativeRootPaths];
            }
            foreach ($relativeRootPaths as $relativeRootPath) {
                $rootPaths[] = new Path(
                    $this->createFullPath($currentPath, $relativeRootPath),
                    $includeNamespace ? (string) $namespace : ''
                );
            }
        }
        return $rootPaths;
    }

    /**
     * @param mixed $relativePaths
     * @return array<string>
     */
    private function normalizeRelativePaths($relativePaths): array
    {
        if (is_string($relativePaths)) {
            return [$relativePaths];
        }
        if (!is_array($relativePaths)) {
            return [];
        }

        return array_values(array_filter($relativePaths, static function ($relativePath): bool {
            return is_string($relativePath);
        }));
    }

    private function createFullPath(string $currentPath, string $relativePath): string
    {
        return PathHelper::removeDoubleSlashes($currentPath . '/' . $relativePath);
    }

    /**
     * @param string $path
     * @return bool
     */
    private function isExcludedPath(string $path): bool
    {
        foreach ($this->excludedPaths as $excludedPath) {
            if (stripos($path, $excludedPath) === 0) {
                return true;
            }
        }
        return false;
    }
}
