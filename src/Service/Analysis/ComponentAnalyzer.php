<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Service\Analysis;

use Chetkov\PHPCleanArchitecture\Model\AnalysisContext;
use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\FileAnalyzedEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\SourceDiscovery\PhpParserSourceUnitDiscovery;
use Chetkov\PHPCleanArchitecture\Service\Analysis\SourceDiscovery\SourceUnitDiscoveryInterface;
use Chetkov\PHPCleanArchitecture\Service\EventManagerInterface;
use Chetkov\PHPCleanArchitecture\Model\Path;
use Chetkov\PHPCleanArchitecture\Model\UnitOfCode;
use Chetkov\PHPCleanArchitecture\Service\Analysis\DependenciesFinder\DependenciesFinderInterface;

/**
 * Class ComponentAnalyzer
 * @package Chetkov\PHPCleanArchitecture\Service\Analysis
 */
class ComponentAnalyzer
{
    /** @var DependenciesFinderInterface */
    private $dependenciesFinder;

    /** @var EventManagerInterface */
    private $eventManager;

    /** @var SourceUnitDiscoveryInterface */
    private $sourceUnitDiscovery;

    /** @var SourceFileFinder */
    private $sourceFileFinder;

    /** @var AnalysisContext */
    private $analysisContext;

    /**
     * @param DependenciesFinderInterface $dependenciesFinder
     * @param EventManagerInterface $eventManager
     * @param SourceUnitDiscoveryInterface|null $sourceUnitDiscovery
     */
    public function __construct(
        DependenciesFinderInterface $dependenciesFinder,
        EventManagerInterface $eventManager,
        AnalysisContext $analysisContext,
        ?SourceUnitDiscoveryInterface $sourceUnitDiscovery = null
    ) {
        $this->dependenciesFinder = $dependenciesFinder;
        $this->eventManager = $eventManager;
        $this->analysisContext = $analysisContext;
        $this->sourceUnitDiscovery = $sourceUnitDiscovery ?? new PhpParserSourceUnitDiscovery();
        $this->sourceFileFinder = new SourceFileFinder();
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

        $analyzedFileIndex = 0;
        $totalFiles = $this->sourceFileFinder->find($component->rootPaths())->count();

        foreach ($component->rootPaths() as $path) {
            /** @var \SplFileInfo $file */
            foreach ($this->sourceFileFinder->find([$path]) as $file) {
                $analyzedFileIndex++;

                $fullPath = $file->getRealPath();
                if (!$fullPath) {
                    continue;
                }

                $fileAnalyzedEvent = new FileAnalyzedEvent($analyzedFileIndex, $totalFiles, $fullPath);

                if ($component->isExcluded($fullPath)) {
                    $fileAnalyzedEvent->toSkipped();
                    $this->eventManager->notify($fileAnalyzedEvent);
                    continue;
                }

                foreach ($this->sourceUnitDiscovery->discover($file, $path) as $sourceUnit) {
                    $unitOfCode = UnitOfCode::createFromSourceUnit($this->analysisContext, $sourceUnit, $component);
                    $dependencies = $this->dependenciesFinder->find($unitOfCode);
                    foreach ($dependencies as $dependency) {
                        $unitOfCode->addOutputDependency(UnitOfCode::create($this->analysisContext, $dependency));
                    }
                }

                $this->eventManager->notify($fileAnalyzedEvent);
            }
        }
    }
}
