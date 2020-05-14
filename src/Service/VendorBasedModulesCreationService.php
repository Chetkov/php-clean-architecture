<?php

namespace Chetkov\PHPCleanArchitecture\Service;

use Chetkov\PHPCleanArchitecture\Helper\PathHelper;
use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Model\Path;

/**
 * Class VendorBasedModulesCreationService
 * @package Chetkov\PHPCleanArchitecture\Service
 */
class VendorBasedModulesCreationService
{
    /** @var string[] */
    private $excludedPaths;

    /**
     * VendorBasedModulesCreationService constructor.
     * @param string[] $excludedPaths
     */
    public function __construct(array $excludedPaths = [])
    {
        $this->excludedPaths = $excludedPaths;
    }

    /**
     * @param string $pathToVendor
     * @return Module[]
     */
    public function create(string $pathToVendor): array
    {
        $modules = [];
        $composerFiles = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pathToVendor)), '/composer.json/i');

        /** @var \SplFileInfo $composerFile */
        foreach ($composerFiles as $composerFile) {
            $filePath = $composerFile->getRealPath();
            if ($this->isExcludedPath($filePath)) {
                continue;
            }

            $content = file_get_contents($filePath);
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

            $modules[] = Module::create($packageName, $rootPaths, $excludedPaths);
        }

        return $modules;
    }

    /**
     * @param array $autoloadSection
     * @param string $currentPath
     * @return Path[]
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
