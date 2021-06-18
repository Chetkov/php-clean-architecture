<?php

namespace Chetkov\PHPCleanArchitecture\Service;

use Chetkov\PHPCleanArchitecture\Helper\Console\Console;
use Chetkov\PHPCleanArchitecture\Helper\PathHelper;
use Chetkov\PHPCleanArchitecture\Model\Component;
use Chetkov\PHPCleanArchitecture\Model\Event\Event\ComponentAnalysisFinishedEvent;
use Chetkov\PHPCleanArchitecture\Model\Event\Event\ComponentAnalysisStartedEvent;
use Chetkov\PHPCleanArchitecture\Model\Event\Event\FileAnalyzedEvent;
use Chetkov\PHPCleanArchitecture\Model\Event\EventManagerInterface;
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

    /** @var EventManagerInterface */
    private $eventManager;

    /**
     * ComponentAnalyzer constructor.
     * @param DependenciesFinderInterface $dependenciesFinder
     * @param EventManagerInterface $eventManager
     */
    public function __construct(DependenciesFinderInterface $dependenciesFinder, EventManagerInterface $eventManager)
    {
        $this->dependenciesFinder = $dependenciesFinder;
        $this->eventManager = $eventManager;
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

        $filesIterator = new CompositeCountableIterator();
        foreach ($component->rootPaths() as $path) {
            $filesIterator->addIterator(
                new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path->path())), '/\.php$/i')
            );
        }

        $analyzedFileIndex = 0;
        $totalFiles = $filesIterator->count();

        /** @var \SplFileInfo $file */
        foreach ($filesIterator as $file) {
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

            $this->eventManager->notify($fileAnalyzedEvent);
        }
    }
}
