<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener;

use Chetkov\PHPCleanArchitecture\Infrastructure\Console\Console;
use Chetkov\PHPCleanArchitecture\Infrastructure\Console\ProgressBar;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\AnalysisStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\ComponentAnalysisStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Analysis\Event\FileAnalyzedEvent;
use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Service\EventListenerInterface;

class FileAnalyzedEventListener implements EventListenerInterface
{
    /** @var AnalysisStartedEvent */
    private $lastAnalysisStartEvent;

    /** @var ComponentAnalysisStartedEvent */
    private $lastComponentAnalysisStartEvent;

    /** @var int */
    private $counter = 0;

    public function handle(EventInterface $event): void
    {
        switch (true) {
            case $event instanceof AnalysisStartedEvent:
                $this->lastAnalysisStartEvent = $event;
                break;
            case $event instanceof ComponentAnalysisStartedEvent:
                $this->lastComponentAnalysisStartEvent = $event;
                break;
            case $event instanceof FileAnalyzedEvent:
                $this->counter++;
                if ($this->counter % 10 !== 0) {
                    return;
                }

                $executionTime = (int) (microtime(true) - $this->lastAnalysisStartEvent->getMicroTime());
                $componentAnalysisProgress = $this->calculateComponentAnalysisProgress($event);
                $fullProgress = $this->calculateFullProgress($componentAnalysisProgress);

                $progressOutput = $this->getFullPProgressBar()->getOutput($fullProgress) .
                    $this->getComponentAnalysisProgressBar()->getOutput($componentAnalysisProgress, sprintf(
                        '[%ss] %s: [%s] %s',
                        $executionTime,
                        $this->lastComponentAnalysisStartEvent->getComponent()->name(),
                        $event->getStatus(),
                        $event->getFullPath()
                    )) . "\r";
                Console::write($progressOutput);
                break;
            default:
        }
    }

    /**
     * @param FileAnalyzedEvent $event
     * @return int
     */
    private function calculateComponentAnalysisProgress(FileAnalyzedEvent $event): int
    {
        return (int) ($event->getPosition() / $event->getTotalPositions() * 100);
    }

    /**
     * @param int $componentAnalysisProgress
     * @return int
     */
    private function calculateFullProgress(int $componentAnalysisProgress): int
    {
        $stage = 100 / $this->lastComponentAnalysisStartEvent->getTotalPositions();
        $progressOfStage = $stage / 100 * $componentAnalysisProgress;
        return (int) ($progressOfStage +
            ($this->lastComponentAnalysisStartEvent->getPosition() /
                $this->lastComponentAnalysisStartEvent->getTotalPositions() * 100));
    }

    /**
     * @return ProgressBar
     */
    private function getFullPProgressBar(): ProgressBar
    {
        return ProgressBar::getInstance(0, 25);
    }

    /**
     * @return ProgressBar
     */
    private function getComponentAnalysisProgressBar(): ProgressBar
    {
        return ProgressBar::getInstance(75, 75);
    }
}
