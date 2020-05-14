<?php

namespace Chetkov\PHPCleanArchitecture\Service;

use Chetkov\PHPCleanArchitecture\Helper\PathHelper;
use Chetkov\PHPCleanArchitecture\Model\Module;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\DependenciesFinderInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ModuleAnalyzer
 * @package Chetkov\PHPCleanArchitecture\Service
 */
class ModuleAnalyzer
{
    /** @var DependenciesFinderInterface */
    private $dependenciesFinder;

    /** @var LoggerInterface */
    private $logger;

    /**
     * ModuleAnalyzer constructor.
     * @param DependenciesFinderInterface $dependenciesFinder
     * @param LoggerInterface $logger
     */
    public function __construct(DependenciesFinderInterface $dependenciesFinder, LoggerInterface $logger)
    {
        $this->dependenciesFinder = $dependenciesFinder;
        $this->logger = $logger;
    }

    /**
     * @param Module $module
     * @return Module
     */
    public function analyze(Module $module): Module
    {
        $this->logger->info('MODULE: '. $module->name());
        foreach ($module->rootPaths() as $path) {
            $files = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path->path())), '/\.php$/i');

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                $fullPath = $file->getRealPath();
                if ($module->isExcluded($fullPath)) {
                    $this->logger->warning("[SKIPPED] $fullPath");
                    continue;
                }

                $fullName = PathHelper::removeDoubleBackslashes(
                    $path->namespace() . PathHelper::pathToNamespace(
                        $path->getRelativePath($fullPath)
                    )
                );

                $unitOfCode = UnitOfCode::create($fullName, $module, $fullPath);
                $dependencies = $this->dependenciesFinder->find($unitOfCode);
                foreach ($dependencies as $dependency) {
                    $unitOfCode->addOutputDependency(UnitOfCode::create($dependency));
                }
                $this->logger->info("[OK] $fullPath");
            }
        }

        return $module;
    }


}
