<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Model\Event\Listener;

use Chetkov\PHPCleanArchitecture\Helper\Console\Console;
use Chetkov\PHPCleanArchitecture\Helper\Console\ProgressBar;
use Chetkov\PHPCleanArchitecture\Model\Event\Event\ComponentAnalysisStartedEvent;
use Chetkov\PHPCleanArchitecture\Model\Event\Event\FileAnalyzedEvent;
use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Model\Event\EventListenerInterface;

class FileAnalyzedEventListener implements EventListenerInterface
{
    /** @var ComponentAnalysisStartedEvent */
    private $lastComponentAnalysisStartEvent;

    public function handle(EventInterface $event): void
    {
        if ($event instanceof ComponentAnalysisStartedEvent) {
            $this->lastComponentAnalysisStartEvent = $event;
            return;
        }

        if (!$event instanceof FileAnalyzedEvent) {
            return;
        }

        $componentAnalysisProgress = $this->calculateComponentAnalysisProgress($event);
        $fullProgress = $this->calculateFullProgress($componentAnalysisProgress);

        $progressOutput = $this->getFullPProgressBar()->getOutput($fullProgress) .
            $this->getComponentAnalysisProgressBar()->getOutput($componentAnalysisProgress, sprintf(
                '%s: [%s] %s',
                $this->lastComponentAnalysisStartEvent->getComponent()->name(),
                $event->getStatus(),
                $event->getFullPath()
            )) . "\r";
        Console::write($progressOutput);
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
