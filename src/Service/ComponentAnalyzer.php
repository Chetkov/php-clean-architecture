<?php

namespace Chetkov\PHPCleanArchitecture\Service;

use Chetkov\PHPCleanArchitecture\Helper\PathHelper;
use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\DependenciesFinder\DependenciesFinderInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ComponentAnalyzer
 * @package Chetkov\PHPCleanArchitecture\Service
 */
class ComponentAnalyzer
{
    /** @var DependenciesFinderInterface */
    private $dependenciesFinder;

    /** @var LoggerInterface */
    private $logger;

    /**
     * ComponentAnalyzer constructor.
     * @param DependenciesFinderInterface $dependenciesFinder
     * @param LoggerInterface $logger
     */
    public function __construct(DependenciesFinderInterface $dependenciesFinder, LoggerInterface $logger)
    {
        $this->dependenciesFinder = $dependenciesFinder;
        $this->logger = $logger;
    }

    /**
     * @param Component $component
     * @return void
     */
    public function analyze(Component $component): void
    {
        if (!$component->isEnabledForAnalysis()) {
            return;
        }

        $this->logger->info('COMPONENT: '. $component->name());
        foreach ($component->rootPaths() as $path) {
            $files = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path->path())), '/\.php$/i');

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                $fullPath = $file->getRealPath();
                if ($component->isExcluded($fullPath)) {
                    $this->logger->warning("[SKIPPED] $fullPath");
                    continue;
                }

                $fullName = PathHelper::removeDoubleBackslashes(
                    $path->namespace() . PathHelper::pathToNamespace(
                        $path->getRelativePath($fullPath)
                    )
                );

                $unitOfCode = UnitOfCode::create($fullName, $component, $fullPath);
                $dependencies = $this->dependenciesFinder->find($unitOfCode);
                foreach ($dependencies as $dependency) {
                    $unitOfCode->addOutputDependency(UnitOfCode::create($dependency));
                }
                $this->logger->info("[OK] $fullPath");
            }
        }
    }


}
