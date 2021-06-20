<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report;

use Chetkov\PHPCleanArchitecture\Infrastructure\Console\Console;
use Chetkov\PHPCleanArchitecture\Infrastructure\Console\ProgressBar;
use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Service\EventListenerInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ComponentReportRenderingStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ReportBuildingStartedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\UnitOfCodeReportRenderedEvent;

class UnitOfCodeReportRenderedEventListener implements EventListenerInterface
{
    /** @var ReportBuildingStartedEvent */
    private $lastReportBuildingStartedEvent;

    /** @var ComponentReportRenderingStartedEvent */
    private $lastComponentReportRenderingStartedEvent;

    /** @var int */
    private $counter = 0;

    public function handle(EventInterface $event): void
    {
        switch (true) {
            case $event instanceof ReportBuildingStartedEvent:
                $this->lastReportBuildingStartedEvent = $event;
                break;
            case $event instanceof ComponentReportRenderingStartedEvent:
                $this->lastComponentReportRenderingStartedEvent = $event;
                break;
            case $event instanceof UnitOfCodeReportRenderedEvent:
                $this->counter++;
                if ($this->counter % 10 !== 0) {
                    return;
                }

                $executionTime = (int) (microtime(true) - $this->lastReportBuildingStartedEvent->getMicroTime());
                $componentReportRenderingProgress = $this->calculateComponentReportRenderingProgress($event);
                $fullProgress = $this->calculateFullProgress($componentReportRenderingProgress);

                $progressOutput = $this->getFullProgressBar()->getOutput($fullProgress) .
                    $this->getComponentReportRenderingProgressBar()->getOutput($componentReportRenderingProgress, sprintf(
                        '[%ss] %s: %s',
                        $executionTime,
                        $this->lastComponentReportRenderingStartedEvent->getComponent()->name(),
                        $event->getUnitOfCode()->name()
                    )) . "\r";
                Console::write($progressOutput);
                break;
            default:
        }
    }

    /**
     * @param UnitOfCodeReportRenderedEvent $event
     * @return int
     */
    private function calculateComponentReportRenderingProgress(UnitOfCodeReportRenderedEvent $event): int
    {
        return (int) ($event->getPosition() / $event->getTotalPositions() * 100);
    }

    /**
     * @param int $componentReportRenderingProgress
     * @return int
     */
    private function calculateFullProgress(int $componentReportRenderingProgress): int
    {
        $stage = 100 / $this->lastComponentReportRenderingStartedEvent->getTotalPositions();
        $progressOfStage = $stage / 100 * $componentReportRenderingProgress;
        return (int) ($progressOfStage +
            ($this->lastComponentReportRenderingStartedEvent->getPosition() /
                $this->lastComponentReportRenderingStartedEvent->getTotalPositions() * 100));
    }

    /**
     * @return ProgressBar
     */
    private function getFullProgressBar(): ProgressBar
    {
        return ProgressBar::getInstance(0, 25);
    }

    /**
     * @return ProgressBar
     */
    private function getComponentReportRenderingProgressBar(): ProgressBar
    {
        return ProgressBar::getInstance(75, 75);
    }
}
