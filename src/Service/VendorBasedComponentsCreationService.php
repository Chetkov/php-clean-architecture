<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service;

use Chetkov\PHPCleanArchitecture\Model\ComponentInterface;
use Chetkov\PHPCleanArchitecture\Service\Helper\PathHelper;
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

    /**
     * @param array<string> $excludedPaths
     */
    public function __construct(array $excludedPaths = [])
    {
        $this->excludedPaths = $excludedPaths;
    }

    /**
     * @param string $pathToVendor
     * @return array<ComponentInterface>
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
            $rootPaths = $this->createPathsByAutoloadSection($autoloadSection, $composerFile->getPath());

            $autoloadDevSection = $composerData['autoload-dev'] ?? [];
            $excludedPaths = $this->createPathsByAutoloadSection($autoloadDevSection, $composerFile->getPath());

            $components[] = Component::create($packageName, $rootPaths, $excludedPaths)
                ->excludeFromAnalyze();
        }

        return $components;
    }

    /**
     * @param array<array> $autoloadSection
     * @param string $currentPath
     * @return array<Path>
     */
    private function createPathsByAutoloadSection(array $autoloadSection, string $currentPath): array
    {
        $rootPaths = [];
        $psr4 = $autoloadSection['psr-4'] ?? [];
        $psr0 = $autoloadSection['psr-0'] ?? [];
        foreach (array_merge($psr4, $psr0) as $namespace => $relativeRootPaths) {
            if (!is_array($relativeRootPaths)) {
                 $relativeRootPaths = [$relativeRootPaths];
            }
            foreach ($relativeRootPaths as $relativeRootPath) {
                $fullPath = PathHelper::removeDoubleSlashes($currentPath . '/' . $relativeRootPath);
                $rootPaths[] = new Path($fullPath, $namespace);
            }
        }
        return $rootPaths;
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
