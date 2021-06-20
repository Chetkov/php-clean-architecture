<?php

declare(strict_types=1);

namespace Chetkov\PHPCleanArchitecture\Infrastructure\Event\Listener\Report;

use Chetkov\PHPCleanArchitecture\Infrastructure\Console\Console;
use Chetkov\PHPCleanArchitecture\Model\Event\EventInterface;
use Chetkov\PHPCleanArchitecture\Service\EventListenerInterface;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ReportBuildingFinishedEvent;
use Chetkov\PHPCleanArchitecture\Service\Report\DefaultReport\Event\ReportBuildingStartedEvent;

class ReportBuildingEventListener implements EventListenerInterface
{
    /** @var float|null */
    private $startedAt;

    public function handle(EventInterface $event): void
    {
        switch (true) {
            case $event instanceof ReportBuildingStartedEvent:
                $this->handleStart($event);
                break;
            case $event instanceof ReportBuildingFinishedEvent:
                $this->handleFinish($event);
                break;
            default:
        }
    }

    /**
     * @param ReportBuildingStartedEvent $event
     */
    private function handleStart(ReportBuildingStartedEvent $event): void
    {
        if (!$this->startedAt) {
            $this->startedAt = $event->getMicroTime();
        }

        Console::write('Report building started.');
        Console::writeln();
    }

    /**
     * @param ReportBuildingFinishedEvent $event
     */
    private function handleFinish(ReportBuildingFinishedEvent $event): void
    {
        $startedAt = $this->startedAt ?? microtime(true);
        $this->startedAt = null;

        $executionTime = round($event->getMicroTime() - $startedAt, 3);
        Console::write(sprintf('Report building finished. Execution time: %s sec.', $executionTime), true);
        Console::writeln();
    }
}
